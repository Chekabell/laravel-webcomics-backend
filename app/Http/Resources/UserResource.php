<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
       /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when(
                $request->user()?->isAdmin() || $request->user()?->id === $this->id,
                $this->email
            ),
            'image' => $this->image,
            'image_url' => $this->getImageUrl(),
            'role' => $this->when(
                $request->user()?->isAdmin(),
                $this->role
            ),
            'is_admin' => $this->isAdmin(),
            'is_writer' => $this->isWriter(),
            'is_reader' => $this->isReader(),
            'stats' => $this->whenLoaded('comics_count', [
                'comics_count' => $this->comics_count,
            ]),
            'comics' => ComicResource::collection($this->whenLoaded('comics')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Получить URL изображения пользователя.
     */
    protected function getImageUrl(): ?string
    {
        if (!$this->image) {
            return $this->getDefaultAvatar();
        }

        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        return Storage::url($this->image);
    }

    /**
     * URL дефолтного аватара.
     */
    protected function getDefaultAvatar(): string
    {
        return asset('images/default-avatar.png');
    }
}
