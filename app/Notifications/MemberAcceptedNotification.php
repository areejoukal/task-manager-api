<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MemberAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Project $project,
        public string $memberName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'member_accepted',
            'title'        => 'انضم عضو جديد',
            'body'         => "{$this->memberName} انضم إلى مشروع \"{$this->project->name}\"",
            'project_id'   => $this->project->id,
            'project_name' => $this->project->name,
            'member_name'  => $this->memberName,
        ];
    }
}