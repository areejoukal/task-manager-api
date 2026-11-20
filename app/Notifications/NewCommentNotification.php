<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCommentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Comment $comment,
        public Task $task,
        public string $commenterName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'new_comment',
            'title'       => 'تعليق جديد',
            'body'        => "{$this->commenterName} علّق على مهمة \"{$this->task->title}\"",
            'task_id'     => $this->task->id,
            'project_id'  => $this->task->project_id,
            'comment_id'  => $this->comment->id,
            'actor_name'  => $this->commenterName,
        ];
    }
}