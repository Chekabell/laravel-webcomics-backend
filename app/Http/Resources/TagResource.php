<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{

    protected bool $showDescription = false;
    protected bool $showUsageCount = false;

    // Фабричный метод для создания с параметрами
    public static function makeWithOptions($resource, array $options = []): self
    {
        $instance = new static($resource);
        $instance->showDescription = $options['description'] ?? false;
        $instance->showUsageCount = $options['usage_count'] ?? false;
        return $instance;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->when(
                $this->showDescription,
                $this->description
            ),
            'usage_count' => $this->when(
                $this->showUsageCount,
                $this->usage_count
            ),
        ];
    }
}
