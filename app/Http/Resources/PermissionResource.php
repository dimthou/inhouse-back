<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
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
            'slug' => $this->slug,
            'description' => $this->description,
            'module' => $this->when(
                str_contains($this->slug, '.'),
                fn() => explode('.', $this->slug)[0]
            ),
            'action' => $this->when(
                str_contains($this->slug, '.'),
                fn() => explode('.', $this->slug)[1] ?? null
            ),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'roles_count' => $this->when(
                $this->relationLoaded('roles'),
                fn() => $this->roles->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
