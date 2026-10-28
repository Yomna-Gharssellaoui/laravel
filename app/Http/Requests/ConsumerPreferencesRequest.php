<?php

namespace App\Http\Requests;

use App\Models\Consumer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared by the consumer's own profile and the admin consumer CRUD.
 */
class ConsumerPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preferences' => ['nullable', 'array'],
            'preferences.*' => [Rule::in(array_keys(Consumer::PREFERENCES))],
            'sustainability_level' => ['nullable', Rule::in(Consumer::LEVELS)],
            'budget_range' => ['nullable', Rule::in(Consumer::BUDGETS)],
            'dietary_restrictions' => ['nullable', 'string', 'max:500'],
            'allergies' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Validated data with comma-separated lists turned into arrays.
     */
    public function preferences(): array
    {
        $data = $this->safe()->only(['preferences', 'sustainability_level', 'budget_range', 'dietary_restrictions', 'allergies']);

        $data['preferences'] = array_values($data['preferences'] ?? []);

        foreach (['dietary_restrictions', 'allergies'] as $field) {
            $value = $data[$field] ?? null;
            $data[$field] = ($value === null || trim($value) === '')
                ? []
                : array_values(array_filter(array_map(fn ($v) => mb_strtolower(trim($v)), explode(',', $value))));
        }

        return $data;
    }
}
