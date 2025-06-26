<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class IsValidImageableUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $response = Http::timeout(5)->head($value);

            if (!$response->successful() || !str_starts_with($response->header('Content-Type'), 'image/')) {
                $fail('O campo :attribute não contém uma URL de imagem válida.');
            }
        } catch (\Exception $e) {
            $fail('Não foi possível acessar a URL fornecida no campo :attribute.');
        }
    }
}
