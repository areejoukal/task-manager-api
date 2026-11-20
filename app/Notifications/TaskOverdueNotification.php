<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(public Task $task) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'task_overdue',
            'title'      => 'مهمة متأخرة',
            'body'       => "مهمة \"{$this->task->title}\" تجاوزت موعدها",
            'task_id'    => $this->task->id,
            'project_id' => $this->task->project_id,
            'due_date'   => $this->task->due_date?->format('Y-m-d'),
        ];
    }
}