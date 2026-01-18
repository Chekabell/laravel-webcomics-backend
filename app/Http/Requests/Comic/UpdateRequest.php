<?php

namespace App\Http\Requests\Comic;

use App\DTOs\UpdateComicDTO;
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
            'title' => 'sometimes|string|max:150',
            'description' => 'sometimes|string',
            'year' => 'sometimes|integer|min:1900|max:' . date('Y')+1,
            'type' => 'sometimes|in:manga,manhwa,manhua,western,other',
            'cover_image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'tags' => 'sometimes|array',
            'tags.*' => 'exists:tags,id',
            'status' => 'sometimes|in:draft,published'
        ];
    }

    public function toDTO()
    {
        return UpdateComicDTO::fromArray($this->validated());
    }
}
