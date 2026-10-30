<?php

namespace App\Console\Commands;

use App\Services\Ml\MlService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Diagnoses the local ML service: URL, loaded models, evaluation scores and one test prediction per module.
 */
class MlCheck extends Command
{
    protected $signature = 'traceverte:ml-check';

    protected $description = 'Check the local machine learning service (one scikit-learn model per module)';

    public function handle(MlService $ml): int
    {
        $this->line('ML_SERVICE_URL : '.(config('services.ml.url') ?: '(vide)'));

        if (! $ml->enabled()) {
            $this->error('Service ML désactivé : renseignez ML_SERVICE_URL=http://127.0.0.1:8001 dans .env puis php artisan config:clear');

            return self::FAILURE;
        }

        Cache::forget('ml.service.down');
        $health = $ml->health();

        if (! $health) {
            $this->error('Service ML injoignable. Démarrez-le dans un autre terminal :');
            $this->line('  cd ml');
            $this->line('  .venv\Scripts\activate        (Windows)   ou   source .venv/bin/activate');
            $this->line('  uvicorn service:app --port 8001');

            return self::FAILURE;
        }

        $this->table(['Module', 'Modèle', 'Algorithme', 'Scores'], collect($ml->models() ?? [])->map(fn ($m) => [
            $m['module'] ?? '?',
            $m['title'] ?? $m['name'],
            $m['algorithm'] ?? 'NON ENTRAÎNÉ (python train_all.py)',
            collect($m['metrics'] ?? [])->reject(fn ($v) => is_array($v))->take(3)->map(fn ($v, $k) => "$k=$v")->implode(', '),
        ])->all());

        $samples = [
            'M4 avis' => fn () => $ml->reviewAnalysis('Excellent produit, très frais, je recommande !'),
            'M5 greenwashing' => fn () => $ml->greenwashing('100 % naturel et respectueux de la planète.'),
        ];
        foreach ($samples as $label => $call) {
            $result = $call();
            $this->line($label.' : '.($result ? json_encode($result, JSON_UNESCAPED_UNICODE) : 'échec'));
        }

        $loaded = collect($health['models'] ?? [])->filter()->count();
        $total = count($health['models'] ?? []);
        if ($loaded < $total) {
            $this->warn("$loaded/$total modèles chargés : lancez « python train_all.py » dans ml/ puis redémarrez le service.");

            return self::FAILURE;
        }

        $this->info("Service ML opérationnel : $loaded/$total modèles chargés.");

        return self::SUCCESS;
    }
}
