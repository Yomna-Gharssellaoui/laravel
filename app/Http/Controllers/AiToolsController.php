<?php

namespace App\Http\Controllers;

use App\Models\EnvironmentalFootprint;
use App\Models\Product;
use App\Services\Ml\MlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Consumer-facing tools built on the local ML models (ml/): greenwashing checker (M5),
 * footprint simulator (M1) and live review assistant (M4).
 */
class AiToolsController extends Controller
{
    /** Values the footprint model was trained on (ml/modules/m1_footprint.py) */
    public const CATEGORIES = ['huiles', 'fruits secs', 'fruits', 'légumes', 'condiments', 'céréales', 'épicerie sucrée', 'épicerie', 'produits laitiers', 'boissons', 'conserves'];

    public const FARMING = ['biologique', 'permaculture', 'raisonnée', 'conventionnelle'];

    public const TRANSFORMATIONS = ['aucune', 'pressage à froid', 'conserverie', 'meunerie', 'séchage solaire', 'laiterie', 'autre'];

    public const EXAMPLES = [
        '100 % naturel et respectueux de la planète.',
        'Huile certifiée bio par Ecocert, certificat n° ECO-TN-2025-0187 consultable en ligne.',
        'Jusqu’à 80 % d’impact en moins !',
        'Consommation d’eau réduite de 30 % depuis 2023, relevés de compteur publiés.',
    ];

    public function index(MlService $ml): View
    {
        return $this->page($ml);
    }

    public function greenwashing(Request $request, MlService $ml): View
    {
        $data = $request->validate(['claim' => ['required', 'string', 'min:5', 'max:1000']]);

        return $this->page($ml, ['claim' => $data['claim'], 'greenwashing' => $ml->greenwashing($data['claim'])]);
    }

    public function footprint(Request $request, MlService $ml): View
    {
        $features = $request->validate([
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'farming_method' => ['required', Rule::in(self::FARMING)],
            'transformation_type' => ['required', Rule::in(self::TRANSFORMATIONS)],
            'distance_km' => ['required', 'numeric', 'min:0', 'max:20000'],
            'organic_certified' => ['nullable', 'boolean'],
        ]);
        $features['distance_km'] = (float) $features['distance_km'];
        $features['organic_certified'] = (bool) ($features['organic_certified'] ?? false);

        $estimate = $ml->estimateFootprint($features);

        return $this->page($ml, [
            'features' => $features,
            'estimate' => $estimate,
            'score' => $estimate ? $this->score($estimate) : null,
            'improvements' => $estimate ? $this->improvements($ml, $features, $estimate) : [],
            'comparison' => $estimate ? $this->comparison($features['category']) : collect(),
        ]);
    }

    /**
     * Live feedback while a consumer writes a review (sentiment, rating mismatch, spam).
     */
    public function reviewCheck(Request $request, MlService $ml): JsonResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'min:10', 'max:2300'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $analysis = $ml->reviewAnalysis($data['text']);
        if (! $analysis) {
            return response()->json(['available' => false]);
        }

        $rating = $data['rating'] ?? null;
        $tips = [];
        if ($analysis['spam_probability'] >= 50) {
            $tips[] = 'Votre texte ressemble à un message publicitaire (liens, promotions, numéros) : il risque d’être refusé.';
        }
        if ($rating && (($rating <= 2 && $analysis['sentiment'] === 'positif') || ($rating >= 4 && $analysis['sentiment'] === 'négatif'))) {
            $tips[] = "Votre note ({$rating}/5) et le ton de votre texte semblent contradictoires : vérifiez la note choisie.";
        }

        return response()->json([
            'available' => true,
            'sentiment' => $analysis['sentiment'],
            'confidence' => $analysis['confidence'],
            'spam_probability' => $analysis['spam_probability'],
            'tips' => $tips,
        ]);
    }

    private function page(MlService $ml, array $data = []): View
    {
        return view('frontend.ai-tools.index', $data + [
            'available' => $ml->enabled(),
            'categories' => self::CATEGORIES,
            'farming' => self::FARMING,
            'transformations' => self::TRANSFORMATIONS,
            'examples' => self::EXAMPLES,
        ]);
    }

    private function score(array $estimate): ?int
    {
        return (new EnvironmentalFootprint([
            'co2_emissions' => $estimate['co2_emissions']['value'],
            'water_usage' => $estimate['water_usage']['value'],
            'land_usage' => $estimate['land_usage']['value'],
        ]))->computeScore();
    }

    /**
     * "What if" scenarios run through the same model: what would change the footprint most.
     *
     * @return list<array{label: string, co2: float, gain: int}>
     */
    private function improvements(MlService $ml, array $features, array $estimate): array
    {
        $scenarios = [];
        if (! in_array($features['farming_method'], ['biologique', 'permaculture'], true)) {
            $scenarios['Passer en agriculture biologique certifiée'] = ['farming_method' => 'biologique', 'organic_certified' => true];
        }
        if ($features['distance_km'] > 50) {
            $scenarios['Vendre à moins de 50 km (circuit court)'] = ['distance_km' => 50.0];
        }
        if (! in_array($features['transformation_type'], ['aucune', 'séchage solaire'], true)) {
            $scenarios['Transformation légère (séchage solaire)'] = ['transformation_type' => 'séchage solaire'];
        }

        $base = $estimate['co2_emissions']['value'];
        $results = [];
        foreach ($scenarios as $label => $change) {
            $co2 = $ml->estimateFootprint(array_merge($features, $change))['co2_emissions']['value'] ?? null;
            if ($co2 !== null && $base > 0 && $co2 < $base) {
                $results[] = ['label' => $label, 'co2' => $co2, 'gain' => (int) round((1 - $co2 / $base) * 100)];
            }
        }

        return collect($results)->sortByDesc('gain')->values()->all();
    }

    private function comparison(string $category)
    {
        return Product::with('environmentalFootprint')
            ->where('status', 'published')
            ->where('category', $category)
            ->whereHas('environmentalFootprint')
            ->get();
    }
}
