<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Project $project,
        public string $inviterName,
        public string $role,
        public int $memberId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $roleLabels = [
            'owner'  => 'مالك',
            'editor' => 'محرر',
            'viewer' => 'مشاهد',
        ];

        return [
            'type'         => 'project_invitation',
            'title'        => 'دعوة للانضمام إلى مشروع',
            'body'         => "{$this->inviterName} دعاك للانضمام إلى \"{$this->project->name}\" كـ " . ($roleLabels[$this->role] ?? $this->role),
            'project_id'   => $this->project->id,
            'project_name' => $this->project->name,
            'inviter'      => $this->inviterName,
            'role'         => $this->role,
            'member_id'    => $this->memberId,
        ];
    }
}