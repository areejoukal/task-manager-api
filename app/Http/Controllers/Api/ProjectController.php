<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $projects = Project::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('members', fn ($q) => $q->where('user_id', $userId));
            })
            ->withCount('tasks')
            ->orderByDesc('created_at')
            ->get();

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $request->user()->projects()->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المشروع بنجاح',
            'data'    => new ProjectResource($project->loadCount('tasks')),
        ], 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $project->load([
            'tasks' => fn ($q) => $q->whereNull('parent_id')
                ->orderBy('position')
                ->orderByDesc('created_at')
                ->with('tags')                 // ⭐ هنا
                ->withCount('comments')
                ->withCount('children')
                ->withCount('attachments')
                ->with('children'),
            'members.user',
        ]);

        return response()->json([
            'success' => true,
            'data'    => new ProjectResource($project),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المشروع بنجاح',
            'data'    => new ProjectResource($project->loadCount('tasks')),
        ]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المشروع بنجاح',
        ]);
    }
}