import pandas as pd
import numpy as np

# Tetapan jumlah data
num_rows = 5000

# 1. Jana 'Days_in_Culture' (Umur ikan dari 1 hingga 120 hari)
days_in_culture = np.random.randint(1, 121, num_rows)

# 2. Jana Suhu (20°C - 38°C)
# 80% normal (26-30), 20% ekstrem (<26 atau >30)
temperature = np.where(np.random.rand(num_rows) < 0.8,
                       np.random.uniform(26, 30, num_rows),
                       np.random.uniform(20, 38, num_rows))

# 3. Jana pH (4.0 - 10.0)
# 80% normal (6.5-8.5), 20% ekstrem (<6.5 atau >8.5)
ph = np.where(np.random.rand(num_rows) < 0.8,
              np.random.uniform(6.5, 8.5, num_rows),
              np.random.uniform(4.0, 10.0, num_rows))

# 4. Jana Kekeruhan (0 - 50 NTU)
# 85% jernih (<20), 15% keruh (>20)
turbidity = np.where(np.random.rand(num_rows) < 0.85,
                     np.random.uniform(0, 20, num_rows),
                     np.random.uniform(20, 50, num_rows))

# 5. Tentukan Status Risiko (Logik Biologi Tilapia)
risk_status = []
for t, p, turb in zip(temperature, ph, turbidity):
    if (26 <= t <= 30) and (6.5 <= p <= 8.5) and (turb <= 20):
        risk_status.append('Stable')
    elif (t > 34) or (t < 22) or (p < 5.5) or (p > 9.0) or (turb > 35):
        risk_status.append('Danger')
    else:
        risk_status.append('Warning')

# 6. Kira Anggaran Berat Berdasarkan Hari (Formula Logistik Tumbesaran)
# Maksimum berat ~350g pada hari ke-120
weight = 350 / (1 + np.exp(-0.05 * (days_in_culture - 60)))
# Tambah sedikit rawak (noise) supaya nampak natural (+/- 2g)
weight = weight + np.random.uniform(-2, 2, num_rows)
weight = np.round(np.clip(weight, 1, 400), 2)

# Buat DataFrame
df = pd.DataFrame({
    'Days_in_Culture': days_in_culture,
    'Temperature_C': np.round(temperature, 2),
    'pH_Level': np.round(ph, 2),
    'Turbidity_NTU': np.round(turbidity, 2),
    'Estimated_Weight_g': weight,
    'Risk_Status': risk_status
})

# Simpan ke Excel
df.to_excel('Dataset_Tilapia_FYP.xlsx', index=False)
print("Dataset berjaya dijana! Sila periksa fail 'Dataset_Tilapia_FYP.xlsx'")