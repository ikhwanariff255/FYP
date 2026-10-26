import warnings
warnings.filterwarnings('ignore')
import sys
import os
import joblib
import numpy as np

def main():
    try:
        suhu = float(sys.argv[1])
        ph = float(sys.argv[2])
        kekeruhan = float(sys.argv[3])
        hari = float(sys.argv[4])
        berat_awal = float(sys.argv[5]) # Terima Berat Awal (Parameter ke-5)

        # Model Risiko (3 Ciri), Model Berat (5 Ciri)
        features_risk = np.array([[suhu, ph, kekeruhan]])
        features_weight = np.array([[suhu, ph, kekeruhan, hari, berat_awal]])

        # Dapatkan laluan folder semasa secara dinamik
        base_dir = os.path.dirname(os.path.abspath(__file__))

        risk_model = joblib.load(os.path.join(base_dir, 'model_risk_status.pkl'))
        weight_model = joblib.load(os.path.join(base_dir, 'model_weight.pkl'))

        risk_status = str(risk_model.predict(features_risk)[0])
        predicted_weight = float(weight_model.predict(features_weight)[0])
        
        print(f"{risk_status}|{round(predicted_weight, 2)}")

    except Exception as e:
        # print(f"DEBUG ERROR: {e}")
        print("Error|50.0")

if __name__ == "__main__":
    main()