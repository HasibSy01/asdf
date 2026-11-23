"""
Modul Random Forest untuk Prediksi Risiko Batas Konsumsi Makanan MBG NutriView
SPPG Ujung Lare - Kota Parepare
Berdasarkan Keputusan Kepala BGN No. 401.1 Tahun 2025 (Batas kritis 4 jam setelah matang)
"""
import sys
import json
from datetime import datetime
from pathlib import Path

MODEL_PATH = Path(__file__).resolve().parent / 'rf_model.joblib'


def predict_with_model(durasi_kemas, estimasi_antar_menit, total_estimasi_tiba_menit, porsi, stok_kritis=0, suhu=30.0):
    if not MODEL_PATH.is_file():
        return None
    try:
        import joblib
        model = joblib.load(MODEL_PATH)
        features = [[durasi_kemas, estimasi_antar_menit, total_estimasi_tiba_menit, porsi, stok_kritis, suhu]]
        proba = model.predict_proba(features)[0]
        pred_idx = int(model.predict(features)[0])
        kelas = 'Tidak Layak' if pred_idx == 1 else 'Layak'
        prob_tidak_layak = float(proba[1]) * 100.0 if len(proba) > 1 else (75.0 if pred_idx == 1 else 20.0)
        return {
            'mode': 'random_forest_joblib',
            'kelas': kelas,
            'probabilitas': round(prob_tidak_layak, 2),
        }
    except Exception:
        return None


def predict_risk(data):
    waktu_matang_str = data.get('waktu_matang')
    waktu_kemas_selesai_str = data.get('waktu_kemas_selesai')
    estimasi_antar_menit = int(data.get('estimasi_antar_menit', 30))
    porsi = int(data.get('porsi', 300))
    stok_kritis = int(data.get('stok_kritis_flag', 0))
    suhu = float(data.get('suhu_lingkungan_c', 30.0))

    if not waktu_matang_str or not waktu_kemas_selesai_str:
        return {
            'success': False,
            'message': 'Data waktu tidak lengkap'
        }

    try:
        waktu_matang = datetime.fromisoformat(waktu_matang_str)
        waktu_kemas = datetime.fromisoformat(waktu_kemas_selesai_str)

        # Selisih waktu matang ke selesai kemas (menit)
        durasi_kemas = (waktu_kemas - waktu_matang).total_seconds() / 60.0
        total_estimasi_tiba_menit = durasi_kemas + estimasi_antar_menit

        model_result = predict_with_model(
            durasi_kemas,
            estimasi_antar_menit,
            total_estimasi_tiba_menit,
            porsi,
            stok_kritis,
            suhu,
        )

        if model_result:
            kelas = model_result['kelas']
            probabilitas = model_result['probabilitas']
            mode = model_result['mode']
        else:
            mode = 'decision_boundary_rule'
            # Ambang batas 4 jam = 240 menit
            if total_estimasi_tiba_menit > 240:
                kelas = 'Tidak Layak'
                probabilitas = min(98.0, 75.0 + (total_estimasi_tiba_menit - 240) * 0.5)
            elif total_estimasi_tiba_menit >= 200:
                kelas = 'Tidak Layak'
                probabilitas = 64.0
            else:
                kelas = 'Layak'
                probabilitas = max(10.0, (total_estimasi_tiba_menit / 240.0) * 30.0)

        return {
            'success': True,
            'mode': mode,
            'kelas': kelas,
            'probabilitas': round(probabilitas, 2),
            'total_estimasi_tiba_menit': total_estimasi_tiba_menit,
            'keterangan': 'Hasil rekomendasi adalah bahan pertimbangan. Keputusan akhir berada pada petugas yang berwenang.'
        }
    except Exception as e:
        return {
            'success': False,
            'error': str(e)
        }

if __name__ == '__main__':
    if len(sys.argv) > 1:
        try:
            payload = json.loads(sys.argv[1])
            res = predict_risk(payload)
            print(json.dumps(res))
        except Exception as e:
            print(json.dumps({'success': False, 'error': str(e)}))
    else:
        print(json.dumps({'success': False, 'error': 'No input argument'}))

