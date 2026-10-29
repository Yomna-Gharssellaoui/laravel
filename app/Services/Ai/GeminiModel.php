<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Google Gemini through the Generative Language REST API (key from Google AI Studio).
 */
class GeminiModel implements LanguageModel
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    /**
     * @param  string|null  $thinkingLevel  Gemini 3 thinking depth ("minimal", "low", "high"); null = model default
     */
    public function __construct(
        private string $apiKey,
        private string $model,
        private int $timeout = 60,
        private ?string $thinkingLevel = null,
    ) {}

    public function name(): string
    {
        return $this->model;
    }

    public function json(string $system, string $prompt, array $schema): array
    {
        $text = $this->generate($system, $prompt, [
            'responseMimeType' => 'application/json',
            'responseSchema' => self::toGeminiSchema($schema),
        ]);

        return json_decode($text, true, flags: JSON_THROW_ON_ERROR);
    }

    public function text(string $system, string $prompt): string
    {
        return $this->generate($system, $prompt);
    }

    private function generate(string $system, string $prompt, array $generationConfig = []): string
    {
        if ($this->thinkingLevel) {
            // Less thinking = much faster answers, enough for these short tasks
            $generationConfig['thinkingConfig'] = ['thinkingLevel' => $this->thinkingLevel];
        }

        $response = Http::timeout($this->timeout)
            ->withHeaders(['x-goog-api-key' => $this->apiKey])
            ->post(sprintf(self::ENDPOINT, $this->model), [
                'systemInstruction' => ['parts' => [['text' => $system]]],
                'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig' => $generationConfig + ['maxOutputTokens' => 4096],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini HTTP '.$response->status().': '.$response->json('error.message', $response->body()));
        }

        $candidate = $response->json('candidates.0');
        $text = collect($candidate['content']['parts'] ?? [])->pluck('text')->filter()->implode('');

        if (trim($text) === '') {
            throw new RuntimeException('Empty Gemini answer (finishReason: '.($candidate['finishReason'] ?? $response->json('promptFeedback.blockReason', 'unknown')).')');
        }

        return trim($text);
    }

    /**
     * Gemini's responseSchema is an OpenAPI subset: upper-case types, no additionalProperties.
     */
    public static function toGeminiSchema(array $schema): array
    {
        unset($schema['additionalProperties']);

        if (isset($schema['type'])) {
            $schema['type'] = strtoupper($schema['type']);
        }

        if (isset($schema['properties'])) {
            $schema['properties'] = array_map([self::class, 'toGeminiSchema'], $schema['properties']);
        }

        if (isset($schema['items'])) {
            $schema['items'] = self::toGeminiSchema($schema['items']);
        }

        return $schema;
    }
}
