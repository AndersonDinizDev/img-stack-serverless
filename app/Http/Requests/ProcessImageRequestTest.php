<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ProcessImageRequestTest extends FormRequest
{

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
            // Upload básico
            'file' => [
                'required_without:url',
                'file',
                'image',
                'max:10000', // Aumentado para 10MB conforme roadmap
                'mimes:jpg,jpeg,png,webp'
            ],
            'url' => [
                'required_without:file',
                'url',
            ],

            // Identificação
            'filename' => [
                'sometimes', // Não obrigatório, pode ser gerado automaticamente
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_\-\.]+$/',
            ],

            // Transformações básicas (MVP)
            'transform' => [
                'sometimes',
                'string',
                Rule::in(['resize', 'crop', 'format', 'quality', 'auto']),
            ],

            // Parâmetros de resize
            'width' => [
                'sometimes',
                'integer',
                'min:1',
                'max:4096',
            ],
            'height' => [
                'sometimes',
                'integer',
                'min:1',
                'max:4096',
            ],
            'fit' => [
                'sometimes',
                'string',
                Rule::in(['contain', 'cover', 'fill', 'inside', 'outside']),
            ],

            // Parâmetros de crop
            'crop_width' => [
                'required_with:transform,crop',
                'integer',
                'min:1',
                'max:4096',
            ],
            'crop_height' => [
                'required_with:transform,crop',
                'integer',
                'min:1',
                'max:4096',
            ],
            'crop_x' => [
                'required_with:transform,crop',
                'integer',
                'min:0',
            ],
            'crop_y' => [
                'required_with:transform,crop',
                'integer',
                'min:0',
            ],

            // Conversão de formato
            'format' => [
                'sometimes',
                'string',
                Rule::in(['jpg', 'jpeg', 'png', 'webp']),
            ],

            // Otimização de qualidade
            'quality' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
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
            'filename.regex' => 'O nome do arquivo contém caracteres inválidos.',
            'transform.in' => 'A transformação deve ser uma das seguintes: resize, crop, format, quality, auto.',
            'width.min' => 'A largura deve ser no mínimo :min pixels.',
            'width.max' => 'A largura deve ser no máximo :max pixels.',
            'height.min' => 'A altura deve ser no mínimo :min pixels.',
            'height.max' => 'A altura deve ser no máximo :max pixels.',
            'fit.in' => 'O modo de ajuste deve ser um dos seguintes: contain, cover, fill, inside, outside.',
            'crop_width.required_with' => 'A largura do corte é obrigatória quando a transformação é crop.',
            'crop_height.required_with' => 'A altura do corte é obrigatória quando a transformação é crop.',
            'crop_x.required_with' => 'A posição X do corte é obrigatória quando a transformação é crop.',
            'crop_y.required_with' => 'A posição Y do corte é obrigatória quando a transformação é crop.',
            'format.in' => 'O formato deve ser um dos seguintes: jpg, jpeg, png, webp.',
            'quality.min' => 'A qualidade deve ser no mínimo :min%.',
            'quality.max' => 'A qualidade deve ser no máximo :max%.',
        ];
    }

    public function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'Validação falhou',
                'errors' => $validator->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
