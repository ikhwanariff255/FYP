import sys
import pickle
import numpy as np
import os

try:
    temp = float(sys.argv[1])
    ph = float(sys.argv[2])
    turb = float(sys.argv[3])
except Exception as e:
    print(f"Error_Input|0.0")
    sys.exit(1)

try:
    # 1. Dapatkan laluan (path) sebenar folder tempat predict.py ini berada
    current_dir = os.path.dirname(os.path.abspath(__file__))
    
    # 2. Cantumkan secara dinamik dengan nama fail .pkl
    classifier_path = os.path.join(current_dir, 'rf_classifier_model.pkl')
    regressor_path = os.path.join(current_dir, 'rf_regressor_model.pkl')

    # 3. Muat turun model menggunakan laluan mutlak tersebut
    classifier_model = pickle.load(open(classifier_path, 'rb'))
    regressor_model = pickle.load(open(regressor_path, 'rb'))

    input_data = np.array([[temp, ph, turb]])

    predicted_risk = classifier_model.predict(input_data)[0]
    predicted_weight = regressor_model.predict(input_data)[0]

    # Cetak hasil ramalan AI tulen
    print(f"{predicted_risk}|{predicted_weight}", end="")

except Exception as e:
    # TUKAR INI: Jika ralat, ia akan cetak punca ralat supaya kau nampak di sistem!
    print(f"Error_Python: {str(e)}|50.0")