<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'text' => $this->text,

            // Пользователь
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'image_url' => $this->user->image_url,
                    'role' => $this->user->role,
                ];
            }),

            // Комикс
            'comic' => $this->whenLoaded('comic', function () {
                return [
                    'id' => $this->comic->id,
                    'title' => $this->comic->title,
                ];
            }),

            // Ответы
            'parent_id' => $this->parent_id,
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'replies_count' => $this->whenLoaded('replies', function () {
                return $this->replies->count();
            }),
            'has_replies' => $this->hasReplies(),

            // Модерация
            'is_approved' => $this->when(
                $request->user()?->isAdmin() || $request->user()?->id === $this->user_id,
                $this->is_approved
            ),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }

    /**
     * Дополнительные данные для ответа.
     */
    public function with(Request $request): array
    {
        return [
            'actions' => $this->getActions($request),
        ];
    }

    /**
     * Доступные действия для пользователя.
     */
    protected function getActions(Request $request): array
    {
        $actions = [];

        if ($request->user()?->id === $this->user_id) {
            $actions[] = 'edit';
            $actions[] = 'delete';
        }

        if ($request->user()?->isAdmin()) {
            $actions[] = 'approve';
            $actions[] = 'reject';
            $actions[] = 'delete';
        }

        $actions[] = 'reply';

        return $actions;
    }
}
