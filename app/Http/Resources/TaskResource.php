<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'project_id'        => $this->project_id,
            'parent_id'         => $this->parent_id,
            'title'             => $this->title,
            'description'       => $this->description,
            'status'            => $this->status,
            'priority'          => $this->priority,
            'due_date'          => $this->due_date?->format('Y-m-d'),
            'position'          => $this->position,
            'completed_at'      => $this->completed_at?->toISOString(),
            'comments_count'    => $this->whenCounted('comments'),
            'children_count'    => $this->whenCounted('children'),
            'children_done'     => $this->when(
                $this->relationLoaded('children'),
                fn () => $this->children->where('status', 'completed')->count()
            ),
            'attachments_count' => $this->whenCounted('attachments'),
            'tags'              => TagResource::collection($this->whenLoaded('tags')),
            'children'          => TaskResource::collection($this->whenLoaded('children')),
            'attachments'       => AttachmentResource::collection($this->whenLoaded('attachments')),
            'project'           => new ProjectResource($this->whenLoaded('project')),
            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),
        ];
    }
}