<?php

namespace App\Console\Commands;

use App\Services\Ai\AiProvider;
use Illuminate\Console\Command;
use Throwable;

/**
 * Diagnoses the AI configuration: which provider is selected, whether a key is
 * set, and the exact error returned by the API when a test call fails.
 */
class AiCheck extends Command
{
    protected $signature = 'traceverte:ai-check';

    protected $description = 'Check the AI provider configuration with a real test call';

    public function handle(AiProvider $ai): int
    {
        $mask = fn (?string $key) => blank($key) ? '(vide)' : substr($key, 0, 6).'…'.substr($key, -4).' ('.strlen($key).' caractères)';

        $this->table(['Paramètre', 'Valeur'], [
            ['AI_PROVIDER', config('services.ai.provider') ?? '(non défini)'],
            ['GEMINI_API_KEY', $mask(config('services.gemini.api_key'))],
            ['GEMINI_MODEL', config('services.gemini.model') ?? '(non défini)'],
            ['ANTHROPIC_API_KEY', $mask(config('services.anthropic.api_key'))],
            ['Cache de configuration', app()->configurationIsCached() ? 'OUI (lancez php artisan config:clear)' : 'non'],
            ['Certificats curl (php.ini)', ini_get('curl.cainfo') ?: '(non défini)'],
        ]);

        $llm = $ai->current();

        if (! $llm) {
            $this->error('Aucun fournisseur IA actif : la clé est vide ou AI_PROVIDER ne correspond à aucune clé.');
            $this->line('Vérifiez .env (une seule ligne GEMINI_API_KEY=..., sans espace ni guillemets), puis : php artisan config:clear');

            return self::FAILURE;
        }

        $this->info('Fournisseur sélectionné : '.class_basename($llm).' ('.$llm->name().')');
        $this->line('Appel de test en cours…');

        try {
            $answer = $llm->text('Réponds en une phrase courte, en français.', 'Dis bonjour à Trace Verte.');
        } catch (Throwable $e) {
            $this->error('Échec de l’appel : '.$e->getMessage());
            $this->newLine();
            $this->line($this->hint($e->getMessage()));

            return self::FAILURE;
        }

        $this->info('Réponse reçue : '.$answer);
        $this->info('L’IA fonctionne. Rechargez la page du produit et reposez la question.');

        return self::SUCCESS;
    }

    private function hint(string $error): string
    {
        return match (true) {
            str_contains($error, 'SSL') || str_contains($error, 'certificate') => 'Piste : certificats SSL de XAMPP. Dans C:\\xampp\\php\\php.ini, mettez curl.cainfo="C:\\xampp\\apache\\bin\\curl-ca-bundle.crt" et openssl.cafile="C:\\xampp\\apache\\bin\\curl-ca-bundle.crt" (sans ; devant), puis rouvrez PowerShell.',
            str_contains($error, 'API key not valid') || str_contains($error, '401') || str_contains($error, '403') => 'Piste : clé refusée. Recopiez-la depuis https://aistudio.google.com/apikey (sans espace).',
            str_contains($error, 'not found') || str_contains($error, '404') => 'Piste : modèle introuvable. Choisissez un modèle listé dans AI Studio et mettez-le dans GEMINI_MODEL.',
            str_contains($error, '429') || str_contains($error, 'quota') => 'Piste : quota gratuit atteint, réessayez dans une minute.',
            str_contains($error, 'resolve') || str_contains($error, 'timed out') || str_contains($error, 'Connection') => 'Piste : pas d’accès Internet depuis PHP (pare-feu, proxy ou antivirus).',
            default => 'Envoyez ce message pour diagnostic.',
        };
    }
}
