<?php

namespace App\Http\Requests\Comic;

use App\DTOs\StoreComicDTO;
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
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'year' => 'required|integer|min:1900|max:' . date('Y')+1,
            'type' => 'required|in:манга,манхва,маньхуа,западное,другое',
            'cover_image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'tags' => 'sometimes|array',
            'tags.*' => 'exists:tags,id',
            'status' => 'sometimes|in:draft,published'
        ];
    }

    public function toDTO() : StoreComicDTO
    {
        return StoreComicDTO::fromArray($this->validated());
    }
}
