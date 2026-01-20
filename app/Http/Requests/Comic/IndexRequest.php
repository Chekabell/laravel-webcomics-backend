<?php

namespace App\Http\Requests\Comic;

use App\DTOs\IndexComicDTO;
use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
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
        $rules = [
            'per_page' => 'sometimes|integer|min:1',
            'type' => 'nullable|in:манга,манхва,маньхуа,западное,другое',
            'yearMax' => 'nullable|integer|min:1900|max:' . date('Y')+1,
            'yearMin' => 'nullable|integer|min:1900',
            'status' => 'sometimes|in:draft,published',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'search' => 'nullable|string|max:100',
            'sort'=> 'sometimes|in:popular,views_desc,views_asc,ratings_desc,ratings_asc'
        ];

        if ($this->has('yearMin') && $this->has('yearMax')) {
            $rules['yearMax'] .= '|gte:yearMin';
            $rules['yearMin'] .= '|lte:yearMax';
        }

        return $rules;
    }

    public function toDTO(): IndexComicDTO
    {
        return IndexComicDTO::fromArray($this->validated());
    }
}
