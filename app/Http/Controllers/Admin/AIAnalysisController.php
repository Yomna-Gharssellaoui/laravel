<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AIAnalysis;
use App\Models\EnvironmentalClaim;
use App\Models\Product;
use App\Services\GreenwashingAnalyzer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Analyses are produced by the GreenwashingAnalyzer (Claude API, or a local
 * heuristic when no API key is configured). They are never typed in by hand.
 */
class AIAnalysisController extends Controller
{
    public function index(Request $request, GreenwashingAnalyzer $analyzer): View
    {
        $items = AIAnalysis::with(['product', 'environmentalClaim'])
            ->when($request->analysis_type, fn ($q) => $q->where('analysis_type', $request->analysis_type))
            ->when($request->q, function ($q) use ($request) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', '%'.$request->q.'%'));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.ai-analyses.index', [
            'items' => $items,
            'aiEnabled' => $analyzer->aiEnabled(),
            'modelName' => $analyzer->modelName(),
        ]);
    }

    public function show(AIAnalysis $ai_analysis): View
    {
        $ai_analysis->load(['product', 'environmentalClaim']);

        return view('admin.ai-analyses.show', ['item' => $ai_analysis]);
    }

    public function destroy(AIAnalysis $ai_analysis): RedirectResponse
    {
        $ai_analysis->delete();

        return redirect()->route('admin.ai-analyses.index')
            ->with('success', 'Analyse supprimée.');
    }

    public function analyzeClaim(EnvironmentalClaim $environmental_claim, GreenwashingAnalyzer $analyzer): RedirectResponse
    {
        abort_if($environmental_claim->product_id === null, 422, 'Cette déclaration n’est liée à aucun produit.');

        $analysis = $analyzer->analyzeClaim($environmental_claim);

        return redirect()->route('admin.ai-analyses.show', $analysis)
            ->with('success', $this->message($analysis));
    }

    public function analyzeProduct(Product $product, GreenwashingAnalyzer $analyzer): RedirectResponse
    {
        $analysis = $analyzer->analyzeProduct($product);

        return redirect()->route('admin.ai-analyses.show', $analysis)
            ->with('success', $this->message($analysis));
    }

    private function message(AIAnalysis $analysis): string
    {
        return $analysis->is_demo
            ? 'Analyse heuristique enregistrée (aucune clé IA configurée, ou l’API n’a pas répondu : voir storage/logs/laravel.log).'
            : 'Analyse IA enregistrée ('.$analysis->model_used.').';
    }
}
