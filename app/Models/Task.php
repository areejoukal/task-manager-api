<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'parent_id',
        'user_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'position',
        'completed_at',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'completed_at' => 'datetime',
        'position'     => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // أضف هذه العلاقات

public function parent(): BelongsTo
{
    return $this->belongsTo(Task::class, 'parent_id');
}

public function children(): HasMany
{
    return $this->hasMany(Task::class, 'parent_id')->orderBy('position');
}

// دالة مساعدة: هل هذه مهمة فرعية؟
public function isSubtask(): bool
{
    return !is_null($this->parent_id);
}
public function attachments(): HasMany
{
    return $this->hasMany(Attachment::class)->latest();
}
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class, 'task_tag');
}
}
