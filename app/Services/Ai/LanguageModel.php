<?php

namespace App\Services\Ai;

/**
 * A large language model provider (Claude, Gemini…) used by the AI features.
 */
interface LanguageModel
{
    /** Label stored with each analysis, e.g. "claude-opus-5-5" or "gemini-2.5-flash". */
    public function name(): string;

    /**
     * Ask for a JSON object matching a (JSON Schema subset) schema.
     *
     * @param  array<string, mixed>  $schema  object schema with "properties" and "required"
     * @return array<string, mixed>
     */
    public function json(string $system, string $prompt, array $schema): array;

    /** Ask for a short free-text answer. */
    public function text(string $system, string $prompt): string;
}
