<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'task_id'     => $this->task_id,
            'filename'    => $this->filename,
            'mime_type'   => $this->mime_type,
            'size'        => $this->size,
            'human_size'  => $this->human_size,
            'is_image'    => $this->isImage(),
            'url'         => asset('storage/' . $this->path),
            'uploader'    => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ],
            'is_mine'     => $request->user()?->id === $this->user_id,
            'created_at'  => $this->created_at?->toISOString(),
            'attachments_count' => $this->whenCounted('attachments'),
            'attachments'       => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}