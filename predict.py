import sys
import joblib
import numpy as np

def main():
    try:
        # PENYELESAIAN ISU 2a: Tukar string dari Laravel kepada Float
        # sys.argv[0] adalah nama fail (predict.py)
        # Indeks mesti sepadan dengan: [$pythonPath, 'predict.py', $temperature, $ph, $turbidity]
        suhu = float(sys.argv[1])
        ph = float(sys.argv[2])
        kekeruhan = float(sys.argv[3])

        # PENYELESAIAN ISU 2b: Susunan array mesti SAMA SEBIJI macam masa training data
        features = np.array([[suhu, ph, kekeruhan]])

        # JIKA AWAK GUNA SCALER MASA TRAINING, WAJIB BUKA KOMEN DI BAWAH:
        # scaler = joblib.load(r'C:\laragon\www\fyp\scaler.pkl')
        # features = scaler.transform(features)

        # Load model (Guna absolute path supaya Process Laravel tak sesat)
        model = joblib.load(r'C:\laragon\www\fyp\model_iot_tilapia.pkl')
        
        # Buat prediction
        prediction = model.predict(features)

        # Bergantung kepada bagaimana model awak dilatih. 
        # Jika model mengeluarkan 1 output (risk_status sahaja):
        risk_status = prediction[0]
        
        # Jika ada logik berasingan untuk berat, letak di sini.
        # Sebagai contoh sementara, kita hardcode 0.0 jika model awak tak support berat lagi.
        estimated_weight = 0.0 

        # Cetak output dalam format "Status|Berat" yang diminta oleh AiPredictionService.php
        print(f"{risk_status}|{estimated_weight}")

    except Exception as e:
        # Cetak ralat ke Standard Error supaya Laravel Log::error boleh tangkap
        print(f"Ralat Python: {str(e)}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()