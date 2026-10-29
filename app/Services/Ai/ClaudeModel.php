<?php

namespace App\Services\Ai;

use Anthropic\Client;
use RuntimeException;

class ClaudeModel implements LanguageModel
{
    public function __construct(private string $apiKey, private string $model) {}

    public function name(): string
    {
        return $this->model;
    }

    public function json(string $system, string $prompt, array $schema): array
    {
        $text = $this->ask($system, $prompt, 4000, [
            'effort' => 'low',
            'format' => ['type' => 'json_schema', 'schema' => $schema],
        ]);

        return json_decode($text, true, flags: JSON_THROW_ON_ERROR);
    }

    public function text(string $system, string $prompt): string
    {
        return $this->ask($system, $prompt, 2000, ['effort' => 'low']);
    }

    private function ask(string $system, string $prompt, int $maxTokens, array $outputConfig): string
    {
        // Beta endpoint only for the server-side fallback: if the model declines,
        // the API retries on a fallback model instead of returning a refusal
        $message = (new Client(apiKey: $this->apiKey))->beta->messages->create(
            model: $this->model,
            maxTokens: $maxTokens,
            system: $system,
            messages: [['role' => 'user', 'content' => $prompt]],
            outputConfig: $outputConfig,
            fallbacks: 'default',
            betas: ['server-side-fallback-2026-07-01'],
        );

        if ($message->stopReason === 'refusal') {
            throw new RuntimeException('Claude declined the request');
        }

        $text = collect($message->content)
            ->filter(fn ($block) => $block->type === 'text')
            ->map(fn ($block) => $block->text)
            ->implode("\n");

        return trim($text) !== '' ? trim($text) : throw new RuntimeException('Empty Claude answer');
    }
}
