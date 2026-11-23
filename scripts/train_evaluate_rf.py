"""
Skrip Pelatihan & Evaluasi Model Random Forest Classifier (KPI-07 & AC-06).

Membandingkan performa RandomForestClassifier terhadap model baseline
(DecisionTreeClassifier, LogisticRegression, dan Aturan Ambang Batas 240m)
menggunakan metrik Accuracy, Precision, Recall, dan F1-Score, lalu menyimpan
model terlatih ke `scripts/rf_model.joblib` serta laporan `scripts/model_evaluation.json`.
"""

import csv
import json
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent
DATASET_PATH = BASE_DIR / "dataset_simulasi_mbg.csv"
MODEL_OUT = BASE_DIR / "rf_model.joblib"
REPORT_OUT = BASE_DIR / "model_evaluation.json"


def load_dataset():
    features = []
    labels = []
    with DATASET_PATH.open("r", encoding="utf-8") as fh:
        reader = csv.DictReader(fh)
        for row in reader:
            durasi_kemas = float(row["durasi_kemas_menit"])
            estimasi_antar = float(row["estimasi_antar_menit"])
            porsi = float(row["porsi"])
            stok_kritis = float(row["stok_kritis_flag"])
            suhu = float(row["suhu_lingkungan_c"])
            total_waktu = durasi_kemas + estimasi_antar
            features.append([durasi_kemas, estimasi_antar, total_waktu, porsi, stok_kritis, suhu])
            labels.append(1 if row["label_kelayakan"].strip() == "Tidak Layak" else 0)
    return features, labels


def main():
    try:
        from sklearn.ensemble import RandomForestClassifier
        from sklearn.tree import DecisionTreeClassifier
        from sklearn.linear_model import LogisticRegression
        from sklearn.model_selection import StratifiedKFold, cross_validate
        import joblib
    except ImportError as exc:
        print(json.dumps({
            "success": False,
            "error": f"Dependensi scikit-learn/joblib belum terpasang: {exc}. Jalankan: pip install scikit-learn joblib"
        }, indent=2))
        return

    X, y = load_dataset()
    cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)
    scoring = ["accuracy", "precision", "recall", "f1"]

    models = {
        "RandomForestClassifier": RandomForestClassifier(
            n_estimators=150, max_depth=6, random_state=42
        ),
        "DecisionTreeClassifier": DecisionTreeClassifier(max_depth=5, random_state=42),
        "LogisticRegression": LogisticRegression(max_iter=500, random_state=42),
    }

    comparison = {}
    for name, clf in models.items():
        res = cross_validate(clf, X, y, cv=cv, scoring=scoring)
        comparison[name] = {
            "accuracy": round(float(res["test_accuracy"].mean()), 4),
            "precision": round(float(res["test_precision"].mean()), 4),
            "recall": round(float(res["test_recall"].mean()), 4),
            "f1": round(float(res["test_f1"].mean()), 4),
        }

    # Baseline aturan ambang batas sederhana (total_waktu >= 200 menit)
    rule_preds = [1 if row[2] >= 200 else 0 for row in X]
    tp = sum(1 for yt, yp in zip(y, rule_preds) if yt == 1 and yp == 1)
    tn = sum(1 for yt, yp in zip(y, rule_preds) if yt == 0 and yp == 0)
    fp = sum(1 for yt, yp in zip(y, rule_preds) if yt == 0 and yp == 1)
    fn = sum(1 for yt, yp in zip(y, rule_preds) if yt == 1 and yp == 0)
    prec = tp / max(1, tp + fp)
    rec = tp / max(1, tp + fn)
    f1 = (2 * prec * rec) / max(1e-9, prec + rec)
    comparison["RuleBased_200m"] = {
        "accuracy": round((tp + tn) / len(y), 4),
        "precision": round(prec, 4),
        "recall": round(rec, 4),
        "f1": round(f1, 4),
    }

    # Latih final RandomForest pada seluruh dataset & ekspor
    final_rf = models["RandomForestClassifier"].fit(X, y)
    joblib.dump(final_rf, MODEL_OUT)

    report = {
        "success": True,
        "dataset_rows": len(X),
        "features": [
            "durasi_kemas_menit",
            "estimasi_antar_menit",
            "total_waktu_menit",
            "porsi",
            "stok_kritis_flag",
            "suhu_lingkungan_c",
        ],
        "target_classes": {"0": "Layak", "1": "Tidak Layak"},
        "cv_folds": 5,
        "comparison": comparison,
        "model_artifact": str(MODEL_OUT.name),
    }

    REPORT_OUT.write_text(json.dumps(report, indent=2), encoding="utf-8")
    print(json.dumps(report, indent=2))


if __name__ == "__main__":
    main()

