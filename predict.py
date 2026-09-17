import sys
import joblib
import pandas as pd

# Ambil argumen input dari Laravel: [temperature, ph, turbidity]
if len(sys.argv) < 4:
    print("Error: Input tidak mencukupi")
    sys.exit(1)

temp = float(sys.argv[1])
ph = float(sys.argv[2])
turbidity = float(sys.argv[3])

# Muat turun model .pkl yang telah kita latih tadi
clf = joblib.load('rf_classifier_model.pkl')
reg = joblib.load('rf_regressor_model.pkl')

# Format data untuk ramalan
X_new = pd.DataFrame([[temp, ph, turbidity]], columns=['Temperature (°C)', 'pH', 'Turbidity (NTU)'])

# Lakukan ramalan
predicted_risk = clf.predict(X_new)[0]
predicted_weight = reg.predict(X_new)[0]

# Paparkan hasil dalam bentuk format yang mudah dibaca oleh PHP (Cth: Status|Berat)
print(f"{predicted_risk}|{round(predicted_weight, 2)}")