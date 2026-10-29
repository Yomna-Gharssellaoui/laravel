<?php

namespace App\Services\Ai;

/**
 * Picks the language model from .env:
 * AI_PROVIDER=anthropic|gemini, or "auto" (default) = the first provider with a key.
 * Returns null when no key is configured: callers then use their local fallback.
 */
class AiProvider
{
    public function current(): ?LanguageModel
    {
        $provider = config('services.ai.provider', 'auto');

        $claude = filled(config('services.anthropic.api_key'))
            ? new ClaudeModel(config('services.anthropic.api_key'), config('services.anthropic.model'))
            : null;
        $gemini = filled(config('services.gemini.api_key'))
            ? new GeminiModel(config('services.gemini.api_key'), config('services.gemini.model'))
            : null;

        return match ($provider) {
            'anthropic', 'claude' => $claude,
            'gemini', 'google' => $gemini,
            default => $claude ?? $gemini,
        };
    }

    public function enabled(): bool
    {
        return $this->current() !== null;
    }
}
