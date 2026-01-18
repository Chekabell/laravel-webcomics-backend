<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    protected bool $showRole = false;
    protected bool $showImage = false;

    public function __construct($resource,  $index = null, ?array $options = null,)
    {
        parent::__construct($resource);

        if (is_array($options)) {
            $this->showRole = in_array('role',$options) ? true : false;
            $this->showImage = in_array('image',$options) ? true : false;
        }
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
            'name' => $this->name,
            'email' => $this->when(
                $request->user()?->isAdmin() || $request->user()?->id === $this->id,
                $this->email
            ),
            'image' => $this->when(
                $this->showImage,
            $this->image_url
            ),
            'role' => $this->when(
                $this->showRole,
                $this->role
            ),
        ];
    }
}
