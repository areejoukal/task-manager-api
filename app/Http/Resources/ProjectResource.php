<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
{
    return [
        'id'          => $this->id,
        'name'        => $this->name,
        'description' => $this->description,
        'color'       => $this->color,
        'is_owner'    => $request->user()?->id === $this->user_id,
        'my_role'     => $this->relationLoaded('members') && $request->user()
            ? ($this->user_id === $request->user()->id
                ? 'owner'
                : $this->members->firstWhere('user_id', $request->user()->id)?->role)
            : null,
        'members'     => $this->whenLoaded('members', fn () => $this->members->map(fn ($m) => [
            'id'      => $m->id,
            'user_id' => $m->user_id,
            'name'    => $m->user->name,
            'email'   => $m->user->email,
            'role'    => $m->role,
        ])),
        'tasks_count' => $this->whenCounted('tasks'),
        'tasks'       => TaskResource::collection($this->whenLoaded('tasks')),
        'created_at'  => $this->created_at?->toISOString(),
        'updated_at'  => $this->updated_at?->toISOString(),
    ];
}
    
}