<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ml\MlService;
use Illuminate\View\View;

class MlModelsController extends Controller
{
    /** Readable names of the evaluation metrics returned by ml/train_all.py */
    public const METRICS = [
        'r2_co2_emissions' => 'R² CO₂', 'r2_water_usage' => 'R² eau', 'r2_land_usage' => 'R² sols',
        'mae_co2_emissions' => 'Erreur moyenne CO₂ (kg)', 'mae_water_usage' => 'Erreur moyenne eau (L)', 'mae_land_usage' => 'Erreur moyenne sols (m²)',
        'silhouette' => 'Silhouette', 'adjusted_rand_index' => 'Indice de Rand ajusté (profils réels retrouvés)', 'clusters' => 'Taille des groupes',
        'roc_auc' => 'ROC AUC', 'accuracy' => 'Exactitude', 'f1' => 'F1', 'delay_rate' => 'Part de livraisons en retard',
        'sentiment_accuracy' => 'Exactitude sentiment', 'sentiment_macro_f1' => 'F1 macro sentiment',
        'sentiment_accuracy_handwritten' => 'Exactitude sentiment (avis écrits à la main)', 'spam_f1' => 'F1 spam',
        'spam_accuracy_handwritten' => 'Exactitude spam (avis écrits à la main)', 'accuracy_handwritten' => 'Exactitude (textes écrits à la main)',
        'precision_injected' => 'Précision (anomalies injectées)', 'recall_injected' => 'Rappel (anomalies injectées)', 'false_alarm_rate' => 'Taux de fausses alertes',
        'rmse' => 'RMSE (erreur sur la note /5)', 'rmse_baseline_item_mean' => 'RMSE base naïve (moyenne du produit)',
        'improvement_vs_baseline' => 'Gain par rapport à la base', 'n_users' => 'Consommateurs simulés', 'n_ratings' => 'Notes',
    ];

    /** Where each model is used in the application */
    public const USED_IN = [
        'm1_footprint' => 'Fiche produit (back-office) : empreinte estimée, comparaison avec la mesure, pré-remplissage.',
        'm2_producers' => 'Producteurs : colonne « Profil ML » et fiche producteur.',
        'm3_delays' => 'Traces logistiques : risque de retard ; alerte automatique au-delà de 60 %.',
        'm4_reviews' => 'Modération des avis : sentiment et spam ; rejet automatique du spam quasi certain.',
        'm5_greenwashing' => 'Déclarations : analyse de greenwashing (score, expressions en cause).',
        'm5_anomalies' => 'Empreintes : contrôle « Cohérente / Atypique » ; alerte automatique.',
        'm6_recommendations' => 'Recommandations consommateurs : note prédite par les profils proches.',
    ];

    public function index(MlService $ml): View
    {
        return view('admin.ml-models.index', [
            'enabled' => $ml->enabled(),
            'health' => $ml->health(),
            'models' => $ml->models() ?? [],
            'labels' => self::METRICS,
            'usedIn' => self::USED_IN,
        ]);
    }
}
