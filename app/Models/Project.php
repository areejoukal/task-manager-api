<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'color',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function members(): HasMany
{
    return $this->hasMany(ProjectMember::class);
}

public function memberUsers(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'project_members')
        ->withPivot('role')
        ->withTimestamps();
}

public function isMember(int $userId): bool
{
    if ($this->user_id === $userId) return true;

    return $this->members()
        ->where('user_id', $userId)
        ->where('status', 'accepted')  // ← فقط المقبولين
        ->exists();
}

public function canEdit(int $userId): bool
{
    if ($this->user_id === $userId) return true;

    return $this->members()
        ->where('user_id', $userId)
        ->where('status', 'accepted')
        ->whereIn('role', ['owner', 'editor'])
        ->exists();
}

/**
 * أعضاء مقبولون فقط
 */
public function acceptedMembers(): HasMany
{
    return $this->hasMany(ProjectMember::class)->where('status', 'accepted');
}
}