<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskCompletedNotification extends Notification
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
            'type'       => 'task_completed',
            'title'      => 'اكتملت مهمة كاملة!',
            'body'       => "كل مهام \"{$this->task->title}\" الفرعية أُنجزت",
            'task_id'    => $this->task->id,
            'project_id' => $this->task->project_id,
        ];
    }
}