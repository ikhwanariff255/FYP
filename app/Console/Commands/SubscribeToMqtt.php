<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use App\Models\SensorData;
use App\Models\PondDevice;
use App\Models\AiAnalysis;
use App\Services\PondAlertService;
use Symfony\Component\Process\Process;
use Carbon\Carbon;

class SubscribeToMqtt extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Subscribe to MQTT topic, save sensor data, and run AI prediction';

    public function handle()
    {
        $config   = config('mqtt-client.connections.default');
        $server   = $config['host'];
        $port     = (int) $config['port'];
        $clientId = 'laravel-subscriber'; // ID tetap diperlukan untuk sesi berterusan (auto-reconnect)

        // Log masuk ke broker dan sambung semula secara automatik jika terputus
        $settings = (new ConnectionSettings)
            ->setUsername($config['auth']['username'])
            ->setPassword($config['auth']['password'])
            ->setConnectTimeout((int) $config['settings']['connect_timeout'])
            ->setKeepAliveInterval((int) $config['settings']['keep_alive_interval'])
            ->setReconnectAutomatically(true)
            ->setMaxReconnectAttempts(10)
            ->setDelayBetweenReconnectAttempts(5000);

        $this->info("Connecting to MQTT Broker at {$server}:{$port}...");

        try {
            $mqtt = new MqttClient($server, $port, $clientId);
            $mqtt->connect($settings, false);

            // Berhenti dengan kemas apabila systemd hantar SIGTERM
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, fn () => $mqtt->interrupt());
            pcntl_signal(SIGINT, fn () => $mqtt->interrupt());

            $this->info('Listening to MQTT topic: pond/sensor/data...');

            $mqtt->subscribe('pond/sensor/data', function (string $topic, string $message) {
                $this->info("Received message: {$message}");

                $data = json_decode($message, true);

                if ($data && isset($data['mac_address'])) {
                    try {
                        // 1. Cari Peranti berdasarkan MAC Address
                        $device = PondDevice::where('mac_address', $data['mac_address'])->first();

                        if (!$device) {
                            $this->error("Device dengan MAC Address {$data['mac_address']} tidak dijumpai.");
                            return;
                        }

                        // 2. Simpan Data Sensor Mentah
                        $sensorData = SensorData::create([
                            'device_id'   => $device->device_id ?? $device->id,
                            'temperature' => $data['temperature'] ?? 0.0,
                            'ph_level'    => $data['ph_level'] ?? 0.0,
                            'turbidity'   => $data['turbidity'] ?? 0.0,
                            'timestamp'   => now(),
                        ]);

                        // 3. Sediakan Maklumat untuk AI
                        $initialWeight = (float) ($device->initial_weight ?? 5.0);
                        $stockingDate  = $device->stocking_date ? Carbon::parse($device->stocking_date) : Carbon::now();
                        $daysInCulture = max(1, $stockingDate->diffInDays(now()));

                        // Path Python khusus untuk Linux Ubuntu VPS
                        $pythonPath = 'python3';
                        $scriptPath = base_path('predict.py');

                        // 4. Hantar ke Python Predictor
                        $process = new Process([
                            $pythonPath,
                            $scriptPath,
                            (string) $data['temperature'],
                            (string) $data['ph_level'],
                            (string) $data['turbidity'],
                            (string) $daysInCulture,
                            (string) $initialWeight
                        ]);

                        $process->run();

                        $aiOutput = '';
                        if ($process->isSuccessful()) {
                            $aiOutput = trim($process->getOutput());
                        }

                        $lines = explode("\n", $aiOutput);
                        $cleanOutput = '';
                        foreach ($lines as $line) {
                            if (str_contains($line, '|')) {
                                $cleanOutput = trim($line);
                                break;
                            }
                        }

                        $parts = explode('|', $cleanOutput);
                        $riskStatus = !empty($parts[0]) && $parts[0] !== 'Error' ? trim($parts[0]) : 'Stable';
                        $estimatedWeight = isset($parts[1]) ? floatval($parts[1]) : $initialWeight;

                        // 5. Simpan Hasil Analisa AI
                        AiAnalysis::create([
                            'data_id'          => $sensorData->getKey(),
                            'risk_status'      => $riskStatus,
                            'estimated_weight' => $estimatedWeight,
                            'days_in_culture'  => $daysInCulture,
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]);

                        $this->info("SUCCESS: Sensor Data & AI Analysis saved! (data_id: {$sensorData->getKey()})");

                        // 6. Amaran kepada penternak jika keadaan air berubah (tidak menghalang simpanan data)
                        try {
                            app(PondAlertService::class)->evaluate($device);
                        } catch (\Throwable $e) {
                            $this->error('ALERT ERROR: ' . $e->getMessage());
                        }

                    } catch (\Throwable $e) {
                        $this->error("DB/AI ERROR: " . $e->getMessage());
                    }
                }
            }, 0);

            $mqtt->loop(true);
            $mqtt->disconnect();
        } catch (\Exception $e) {
            $this->error('MQTT Connection Error: ' . $e->getMessage());

            // Kod keluar bukan sifar supaya systemd mulakan semula perkhidmatan
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}