<?php

namespace App\Http\Requests\Chapter;

use App\DTOs\UpdateChapterDTO;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'chapter_number' => 'sometimes|integer|min:1',
            'new_chapter_number' => 'sometimes|integer|min:1',
            'chapter_decimal' => 'nullable|numeric|min:0|max:9',
            'new_chapter_decimal' => 'sometimes|numeric|min:0|max:9',
            'pages_zip' => 'sometimes|file|mimes:zip|max:51200',
        ];
    }

    public function toDTO(){
        return UpdateChapterDTO::fromArray($this->validated());
    }
}
