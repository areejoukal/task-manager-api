<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Notifications\TaskCompletedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $query = Task::query()
            ->whereNull('parent_id')
            ->whereHas('project', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('members', fn ($q) => $q->where('user_id', $userId));
            })
            ->with('project')
            ->with('tags')
            ->withCount('comments')
            ->withCount('children')
            ->withCount('attachments')
            ->with('children');

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

        if ($request->filled('tag_id')) {
            $tagId = $request->integer('tag_id');
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $tagId));
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

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $project = Project::findOrFail($request->integer('project_id'));
        $this->authorize('view', $project);

        if ($request->filled('parent_id')) {
            $parent = Task::findOrFail($request->integer('parent_id'));
            $this->authorize('update', $parent);

            if ($parent->parent_id !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إضافة مهمة فرعية لمهمة فرعية.',
                ], 422);
            }
        }

        $data = $request->validated();
        $tagIds = $data['tag_ids'] ?? [];
        unset($data['tag_ids']);

        $task = Task::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        // Sync tags
        if (!empty($tagIds)) {
            $task->tags()->sync($tagIds);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المهمة بنجاح',
            'data'    => new TaskResource(
                $task->load('project')
                     ->load('tags')
                     ->loadCount('comments')
                     ->loadCount('children')
                     ->loadCount('attachments')
            ),
        ], 201);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);
        $task->load('project')
             ->load('tags')
             ->loadCount('comments')
             ->loadCount('children')
             ->loadCount('attachments')
             ->load('children');

        return response()->json([
            'success' => true,
            'data'    => new TaskResource($task),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $data = $request->validated();
        $hasTagIds = $request->has('tag_ids');
        $tagIds = $data['tag_ids'] ?? [];
        unset($data['tag_ids']);

        if (isset($data['status']) && $data['status'] === 'completed' && ! $task->completed_at) {
            $data['completed_at'] = now();
        } elseif (isset($data['status']) && $data['status'] !== 'completed') {
            $data['completed_at'] = null;
        }

        $task->update($data);

        // Sync tags إذا أُرسلت
        if ($hasTagIds) {
            $task->tags()->sync($tagIds);
        }

        if (isset($data['status'])) {
            $this->syncParentStatus($task);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المهمة بنجاح',
            'data'    => new TaskResource(
                $task->fresh()
                     ->load('project')
                     ->load('tags')
                     ->loadCount('comments')
                     ->loadCount('children')
                     ->loadCount('attachments')
                     ->load('children')
            ),
        ]);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $data = ['status' => $request->validated()['status']];

        if ($data['status'] === 'completed' && ! $task->completed_at) {
            $data['completed_at'] = now();
        } elseif ($data['status'] !== 'completed') {
            $data['completed_at'] = null;
        }

        $wasCompleted = $task->status === 'completed';
        $task->update($data);

        $this->syncParentStatus($task);

        if ($task->parent_id) {
            $parent = $task->parent->fresh();
            if (!$wasCompleted && $parent->status === 'completed') {
                $task->user->notify(new TaskCompletedNotification($parent));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة المهمة',
            'data'    => new TaskResource(
                $task->fresh()
                     ->load('project')
                     ->load('tags')
                     ->loadCount('comments')
                     ->loadCount('children')
                     ->loadCount('attachments')
                     ->load('children')
            ),
        ]);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المهمة بنجاح',
        ]);
    }

    public function reorder(ReorderTasksRequest $request): JsonResponse
    {
        $now = now();

        foreach ($request->validated()['tasks'] as $item) {
            $task = Task::find($item['id']);

            if (! $task) {
                abort(404, 'المهمة غير موجودة');
            }

            $this->authorize('update', $task);

            $updates = ['position' => $item['position']];

            if (isset($item['status'])) {
                $updates['status'] = $item['status'];

                if ($item['status'] === 'completed' && ! $task->completed_at) {
                    $updates['completed_at'] = $now;
                } elseif ($item['status'] !== 'completed') {
                    $updates['completed_at'] = null;
                }
            }

            $task->update($updates);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الترتيب',
        ]);
    }

    /**
     * مهام التقويم بين تاريخين.
     */
    public function calendar(Request $request): JsonResponse
    {
        $request->validate([
            'start' => ['nullable', 'date'],
            'end'   => ['nullable', 'date'],
        ]);

        $userId = $request->user()->id;

        $query = Task::query()
            ->whereNull('parent_id')
            ->whereHas('project', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('members', fn ($q) => $q->where('user_id', $userId));
            })
            ->whereNotNull('due_date')
            ->with('project:id,name,color');

        if ($request->filled('start')) {
            $query->whereDate('due_date', '>=', $request->input('start'));
        }

        if ($request->filled('end')) {
            $query->whereDate('due_date', '<=', $request->input('end'));
        }

        $tasks = $query->orderBy('due_date')->get();

        return response()->json([
            'success' => true,
            'data'    => $tasks->map(fn ($t) => [
                'id'        => $t->id,
                'title'     => $t->title,
                'due_date'  => $t->due_date?->format('Y-m-d'),
                'status'    => $t->status,
                'priority'  => $t->priority,
                'project'   => $t->project ? [
                    'id'    => $t->project->id,
                    'name'  => $t->project->name,
                    'color' => $t->project->color,
                ] : null,
            ]),
        ]);
    }

    /**
     * تحديث تاريخ الاستحقاق فقط (Drag & Drop في التقويم).
     */
    public function updateDueDate(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'due_date' => ['nullable', 'date'],
        ]);

        $task->update(['due_date' => $validated['due_date']]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث تاريخ الاستحقاق',
            'data'    => [
                'id'       => $task->id,
                'due_date' => $task->due_date?->format('Y-m-d'),
            ],
        ]);
    }

    private function syncParentStatus(Task $task): void
    {
        if (! $task->parent_id) return;

        $parent = $task->parent;
        if (! $parent) return;

        $total = $parent->children()->count();
        $completed = $parent->children()->where('status', 'completed')->count();

        if ($total === 0) return;

        $newStatus = $completed === $total ? 'completed' : ($completed > 0 ? 'in_progress' : 'pending');

        if ($parent->status !== $newStatus) {
            $parent->update([
                'status' => $newStatus,
                'completed_at' => $newStatus === 'completed' ? now() : null,
            ]);
        }
    }
}