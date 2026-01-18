<?php

namespace App\Http\Requests\Chapter;

use App\DTOs\StoreChapterDTO;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'chapter_number' => 'required|integer|min:1',
            'chapter_decimal' => 'nullable|numeric|min:0|max:9',
            'pages_zip' => 'required|file|mimes:zip|max:51200',
        ];
    }

    public function toDTO(){
        return StoreChapterDTO::fromArray($this->validated());
    }
}
