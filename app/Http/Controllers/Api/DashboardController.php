<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $base = Task::whereNull('parent_id')->whereHas('project', function ($q) use ($userId) {
            $q->where('user_id', $userId)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $userId));
        });

        $total = (clone $base)->count();
        $pending = (clone $base)->where('status', 'pending')->count();
        $inProgress = (clone $base)->where('status', 'in_progress')->count();
        $completed = (clone $base)->where('status', 'completed')->count();

        $overdue = (clone $base)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->count();

        $dueThisWeek = (clone $base)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', now())
            ->whereDate('due_date', '<=', now()->addDays(7))
            ->where('status', '!=', 'completed')
            ->count();

        // توزيع الأولويات
        $byPriority = [
            'low' => (clone $base)->where('priority', 'low')->count(),
            'medium' => (clone $base)->where('priority', 'medium')->count(),
            'high' => (clone $base)->where('priority', 'high')->count(),
        ];

        // مهام آخر 7 أيام (مكتملة)
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $last7Days[] = [
                'date' => $date,
                'label' => now()->subDays($i)->locale('ar')->translatedFormat('D'),
                'completed' => (clone $base)
                    ->whereDate('completed_at', $date)
                    ->count(),
            ];
        }

        // عدد المهام لكل مشروع
        $projectStats = (clone $base)
            ->selectRaw('project_id, COUNT(*) as count')
            ->whereNotNull('project_id')
            ->groupBy('project_id')
            ->with('project:id,name,color')
            ->get()
            ->map(fn ($row) => [
                'project_id' => $row->project_id,
                'name' => $row->project?->name ?? 'محذوف',
                'color' => $row->project?->color ?? '#999999',
                'count' => (int) $row->count,
            ])
            ->sortByDesc('count')
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'pending' => $pending,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'overdue' => $overdue,
                'due_this_week' => $dueThisWeek,
                'by_priority' => $byPriority,
                'last_7_days' => $last7Days,
                'project_stats' => $projectStats,
            ],
        ]);
    }
    public function index(Request $request): AnonymousResourceCollection
{
    $query = Task::query()
        ->where('user_id', $request->user()->id)
        ->with('project')
        ->withCount('comments');

    // البحث النصي (title + description)
    if ($request->filled('search')) {
        $search = $request->string('search')->toString();
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    if ($request->filled('status')) {
        $query->where('status', $request->input('status'));
    }

    if ($request->filled('priority')) {
        $query->where('priority', $request->input('priority'));
    }

    if ($request->filled('project_id')) {
        $query->where('project_id', $request->integer('project_id'));
    }

    if ($request->boolean('overdue')) {
        $query->whereNotNull('due_date')
              ->whereDate('due_date', '<', now())
              ->where('status', '!=', 'completed');
    }

    $tasks = $query
        ->orderByRaw("FIELD(status, 'pending', 'in_progress', 'completed')")
        ->orderBy('position')
        ->orderByDesc('created_at')
        ->limit(100)
        ->get();

    return TaskResource::collection($tasks);
}



public function checkOverdue(Request $request): JsonResponse
{
    $user = $request->user();
    $today = Carbon::today();

    $overdueTasks = Task::where('user_id', $user->id)
        ->whereNull('parent_id')
        ->whereNotNull('due_date')
        ->whereDate('due_date', '<', $today)
        ->where('status', '!=', 'completed')
        ->get();

    $created = 0;
    foreach ($overdueTasks as $task) {
        // لا ترسل إشعاراً مكرراً لنفس اليوم
        $alreadyNotified = $user->notifications()
            ->where('type', TaskOverdueNotification::class)
            ->where('data->task_id', $task->id)
            ->whereDate('created_at', $today)
            ->exists();

        if (!$alreadyNotified) {
            $user->notify(new TaskOverdueNotification($task));
            $created++;
        }
    }

    return response()->json([
        'success' => true,
        'data' => ['created' => $created, 'checked' => $overdueTasks->count()],
    ]);
}
}
