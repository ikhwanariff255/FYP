import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor
import joblib
import warnings
warnings.filterwarnings('ignore')

def train_models():
    print("[~] Menjana 2000 dataset pintar berpandukan pakar biologi Tilapia...")
    np.random.seed(42)
    
    # Jana parameter persekitaran ekstrem dan normal secara rawak
    temp = np.random.uniform(20.0, 38.0, 2000) # Suhu meluas (termasuk yang bahaya)
    ph = np.random.uniform(4.0, 11.0, 2000)    # pH meluas (termasuk sangat berasid/beralkali)
    turb = np.random.uniform(0.0, 50.0, 2000)
    days = np.random.randint(1, 150, 2000)
    initial_weights = np.random.uniform(5.0, 100.0, 2000)
    
    risk_labels = []
    weights = []
    
    for i in range(2000):
        # -----------------------------------------------------
        # 1. LOGIK AI STATUS RISIKO (KUALITI AIR TILAPIA)
        # -----------------------------------------------------
        is_risk = False
        
        # Jika penderia di luar zon selamat, ia dikira berisiko!
        if temp[i] < 25.0 or temp[i] > 32.0:
            is_risk = True
        if ph[i] < 6.5 or ph[i] > 8.5:
            is_risk = True
        if turb[i] > 25.0:
            is_risk = True
            
        risk_status = "At Risk" if is_risk else "Stable"
        risk_labels.append(risk_status)

        # -----------------------------------------------------
        # 2. LOGIK AI PEMBESARAN (BERAT)
        # -----------------------------------------------------
        base_weight = initial_weights[i] + (days[i] * 2.5) 
        
        # Jika air At Risk, ikan stres dan hilang 15% potensi tumbesaran
        if is_risk:
            base_weight -= (base_weight * 0.15) 
            
        weights.append(max(initial_weights[i], base_weight))

    # Masukkan ke dalam jadual (DataFrame)
    df_ai = pd.DataFrame({
        'Temp': temp, 'pH': ph, 'Turbidity': turb,
        'Days_In_Culture': days, 'Initial_Weight': initial_weights,
        'Risk_Status': risk_labels, 'Weight': weights
    })

    # --- TRAIN MODEL 1: KLASIFIKASI RISIKO ---
    X_risk = df_ai[['Temp', 'pH', 'Turbidity']]
    y_risk = df_ai['Risk_Status']
    
    classifier = RandomForestClassifier(n_estimators=100, random_state=42)
    classifier.fit(X_risk, y_risk)
    joblib.dump(classifier, 'model_risk_status.pkl')
    print("[+] Model 1: Risk Status (Pakar Biologi) berjaya di-train!")

    # --- TRAIN MODEL 2: REGRESI BERAT ---
    X_weight = df_ai[['Temp', 'pH', 'Turbidity', 'Days_In_Culture', 'Initial_Weight']]
    y_weight = df_ai['Weight']
    
    regressor = RandomForestRegressor(n_estimators=100, random_state=42)
    regressor.fit(X_weight, y_weight)
    joblib.dump(regressor, 'model_weight.pkl')
    print("[+] Model 2: Weight Regressor (Dinamo Masa) berjaya di-train!")

if __name__ == "__main__":
    train_models()