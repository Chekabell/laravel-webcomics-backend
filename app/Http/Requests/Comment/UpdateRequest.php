<?php

namespace App\Http\Requests\Comment;

use App\DTOs\StoreCommentDTO;
use App\DTOs\UpdateCommentDTO;
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
            'text' => 'required|string|min:1|max:1000',
        ];
    }

    public function toDTO(){
        return UpdateCommentDTO::fromArray($this->validated());
    }
}
