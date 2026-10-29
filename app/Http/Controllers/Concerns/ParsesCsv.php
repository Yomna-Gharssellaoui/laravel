<?php

namespace App\Http\Controllers\Concerns;

trait ParsesCsv
{
    /**
     * "bio, local , " => ['bio', 'local'] ; empty => null
     */
    protected function csvToArray(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
