<?php

namespace App\Filament\Pages\Auth;

use App\Models\PondDevice;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('username')
                    ->label('Username')
                    ->required()
                    ->maxLength(255)
                    ->unique(table: 'user', column: 'username'),

                $this->getEmailFormComponent(),

                TextInput::make('mac_address')
                    ->label('Device MAC Address')
                    ->placeholder('e.g. AA:BB:CC:DD:EE:FF')
                    ->required()
                    ->exists(table: 'pond_device', column: 'mac_address'),

                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $device = PondDevice::where('mac_address', $data['mac_address'])->first();

        return $this->getUserModel()::create([
            'username'  => $data['username'],
            'email'     => $data['email'],
            // Sudah di-hash oleh getPasswordFormComponent(); jangan hash dua kali
            'password'  => $data['password'],
            'role'      => 'Customer',
            'device_id' => $device ? ($device->device_id ?? $device->id) : null,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        // 1. Log keluar sesi auto-login Filament
        auth()->logout();

        // 2. Paparkan notifikasi pendaftaran berjaya
        Notification::make()
            ->title('Registration Successful')
            ->body('Your account has been created successfully. Please log in.')
            ->success()
            ->send();

        // 3. Redirect ke skrin Login Filament
        return filament()->getLoginUrl();
    }
}