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
            'transform' => 'required|array|in:resize,crop,quality,auto',
            'width' => 'sometimes|integer|min:1|max:4096|required_if:transform,resize',
            'height' => 'sometimes|integer|min:1|max:4096|required_if:transform,resize',
            'format' => 'required|string|in:jpg,jpeg,png,webp',
            'quality' => 'sometimes|integer|min:1|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'width.required_if' => 'O campo width é obrigatório, pois resize foi definido no campo transform',
            'height.required_if' => 'O campo width é obrigatório, pois resize foi definido no campo transform',
            'format.required' => 'O campo format é obrigatório',
            'transform.required' => 'O campo transform é obrigatório.',
            'transform.array' => 'O campo transform precisa ser um array.',
            'image.required' => 'O campo image é obrigatório.',
            'image.url' => 'O campo image precisa ser uma URL válida',
            'transform.in' => 'A transformação deve ser uma das seguintes: resize, crop, format, quality, auto.',
            'width.min' => 'A largura deve ser no mínimo :min pixels.',
            'width.max' => 'A largura deve ser no máximo :max pixels.',
            'height.min' => 'A altura deve ser no mínimo :min pixels.',
            'height.max' => 'A altura deve ser no máximo :max pixels.',
            'format.in' => 'O formato deve ser um dos seguintes: jpg, jpeg, png, webp.',
            'quality.min' => 'A qualidade deve ser no mínimo :min%.',
            'quality.max' => 'A qualidade deve ser no máximo :max%.',
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
