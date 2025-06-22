<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class ProcessImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => 'required|url',
            'r_w' => 'integer|min:1|max:4096|required_if:transform,resize',
            'r_h' => 'integer|min:1|max:4096|required_if:transform,resize',
            'i_f' => 'required|string|in:jpg,jpeg,png,webp',
            'i_q' => 'sometimes|integer|min:1|max:100',
            'ai' => 'sometimes|array|in:faces,safe',
        ];
    }

    public function messages(): array
    {
        return [
            'r_w.required_if' => 'O campo width é obrigatório, pois resize foi definido no campo transform',
            'r_h.required_if' => 'O campo width é obrigatório, pois resize foi definido no campo transform',
            'i_f.required' => 'O campo format é obrigatório',
            'image.required' => 'O campo image é obrigatório.',
            'image.url' => 'O campo image precisa ser uma URL válida',
            'r_w.min' => 'A largura deve ser no mínimo :min pixels.',
            'r_w.max' => 'A largura deve ser no máximo :max pixels.',
            'r_h.min' => 'A altura deve ser no mínimo :min pixels.',
            'r_h.max' => 'A altura deve ser no máximo :max pixels.',
            'i_f.in' => 'O formato deve ser um dos seguintes: jpg, jpeg, png, webp.',
            'i_q.min' => 'A qualidade deve ser no mínimo :min%.',
            'i_q.max' => 'A qualidade deve ser no máximo :max%.',
            'ai.in' => 'O campo ai deve ser um dos seguintes: faces, safe.',
            'ai.array' => 'O campo ai deve ser um array.',
        ];
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
