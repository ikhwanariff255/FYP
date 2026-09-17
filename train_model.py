import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor
import joblib

# 1. Baca dataset Excel
df = pd.read_excel('database/seeders/Data_Model_IoTMLCQ_2024.xlsx')
# Pilih ciri (features) input dari sensor untuk ramalan
# Cth: Suhu, pH, Kekeruhan
X = df[['Temperature (°C)', 'pH', 'Turbidity (NTU)']].dropna()

# Target 1: RF Classifier untuk Health Status (Status Risiko)
# Kita padankan target dengan kolum 'Health Status'
y_class = df.loc[X.index, 'Health Status']

# Target 2: RF Regression untuk Average Fish Weight (Anggaran Berat Ikan)
y_reg = df.loc[X.index, 'Average Fish Weight (g)']

# Bahagikan data kepada training dan testing
X_train, X_test, y_c_train, y_c_test = train_test_split(X, y_class, test_size=0.2, random_state=42)
_, _, y_r_train, y_r_test = train_test_split(X, y_reg, test_size=0.2, random_state=42)

# 2. Latih Model Random Forest Classifier (Risiko/Kesihatan)
clf = RandomForestClassifier(n_estimators=100, random_state=42)
clf.fit(X_train, y_c_train)

# 3. Latih Model Random Forest Regressor (Berat Ikan)df = pd.read_excel('Data_Model_IoTMLCQ_2024.xlsx')
reg = RandomForestRegressor(n_estimators=100, random_state=42)
reg.fit(X_train, y_r_train)

# 4. Simpan model yang telah dilatih ke dalam fail
joblib.dump(clf, 'rf_classifier_model.pkl')
joblib.dump(reg, 'rf_regressor_model.pkl')

print("Model Random Forest Classifier & Regressor berjaya dilatih dan disimpan!")