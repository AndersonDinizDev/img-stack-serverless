<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

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
            'file' => [
                'required',
                'file',
                'image',
                'max:' . config('services.image_processing.max_file_size', 10240),
                'mimes:' . implode(',', config('services.image_processing.allowed_formats')),
            ],
            'filename' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_\-\.]+$/',
            ],
            'filetype' => [
                'required',
                'string',
                Rule::in(config('services.image_processing.allowed_formats')),
            ],
            'transformations' => [
                'sometimes',
                'array',
            ],
            'transformations.*' => [
                'string',
                Rule::in(config('services.image_processing.transformations')),
            ],
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
            'crop_width' => [
                'required_if:transformations.*,crop',
                'integer',
                'min:1',
                'max:4096',
            ],
            'crop_height' => [
                'required_if:transformations.*,crop',
                'integer',
                'min:1',
                'max:4096',
            ],
            'crop_x' => [
                'required_if:transformations.*,crop',
                'integer',
                'min:0',
            ],
            'crop_y' => [
                'required_if:transformations.*,crop',
                'integer',
                'min:0',
            ],
            'watermark_path' => [
                'required_if:transformations.*,watermark',
                'string',
                'max:255',
            ],
            'watermark_position' => [
                'sometimes',
                'string',
                Rule::in([
                    'top-left',
                    'top',
                    'top-right',
                    'left',
                    'center',
                    'right',
                    'bottom-left',
                    'bottom',
                    'bottom-right'
                ]),
            ],
            'watermark_offset_x' => [
                'sometimes',
                'integer',
                'min:0',
                'max:100',
            ],
            'watermark_offset_y' => [
                'sometimes',
                'integer',
                'min:0',
                'max:100',
            ],
            'blur_amount' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
            'rotation_angle' => [
                'sometimes',
                'integer',
                'min:-360',
                'max:360',
            ],
            'flip_direction' => [
                'sometimes',
                'string',
                Rule::in(['h', 'v']),
            ],
            'user_id' => [
                'sometimes',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'O arquivo de imagem é obrigatório.',
            'file.image' => 'O arquivo deve ser uma imagem válida.',
            'file.max' => 'O arquivo não pode ser maior que :max KB.',
            'file.mimes' => 'O arquivo deve ser dos tipos: :values.',
            'filename.required' => 'O nome do arquivo é obrigatório.',
            'filename.regex' => 'O nome do arquivo contém caracteres inválidos.',
            'filetype.required' => 'O tipo do arquivo é obrigatório.',
            'filetype.in' => 'O tipo do arquivo deve ser: :values.',
            'transformations.*.in' => 'A transformação :input não é válida.',
            'width.min' => 'A largura deve ser no mínimo :min pixels.',
            'width.max' => 'A largura deve ser no máximo :max pixels.',
            'height.min' => 'A altura deve ser no mínimo :min pixels.',
            'height.max' => 'A altura deve ser no máximo :max pixels.',
            'crop_width.required_if' => 'A largura do corte é obrigatória quando crop está selecionado.',
            'crop_height.required_if' => 'A altura do corte é obrigatória quando crop está selecionado.',
            'watermark_path.required_if' => 'O caminho da marca d\'água é obrigatório quando watermark está selecionado.',
        ];
    }

    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
