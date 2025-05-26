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
            'file' => 'required_without:url|file|image|max:10000|mimes:png,jpg,jpeg,webp',
            'url' => 'required_without:file|url',
            'transform' => 'required|string|in:resize,crop,format,quality,auto',
            'width' => 'sometimes|integer|min:1|max:4096',
            'height' => 'sometimes|integer|min:1|max:4096',
            'format' => 'sometimes|string|in:jpg,jpeg,png,webp',
            'quality' => 'sometimes|integer|min:1|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'file.required_without' => 'É necessário fornecer um arquivo ou uma URL.',
            'url.required_without' => 'É necessário fornecer um arquivo ou uma URL.',
            'file.image' => 'O arquivo deve ser uma imagem válida.',
            'file.max' => 'O arquivo não pode ser maior que :max KB.',
            'file.mimes' => 'O arquivo deve ser dos tipos: :values.',
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
