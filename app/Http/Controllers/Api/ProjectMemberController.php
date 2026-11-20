<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Notifications\MemberAcceptedNotification;
use App\Notifications\ProjectInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $userId = $request->user()->id;
        $isOwner = $project->user_id === $userId;

        $query = $project->members()->with('user');

        if (!$isOwner) {
            // غير المالك يرى فقط المقبولين
            $query->where('status', 'accepted');
        }

        $members = $query->get()->map(fn ($m) => [
            'id'      => $m->id,
            'user_id' => $m->user_id,
            'name'    => $m->user->name,
            'email'   => $m->user->email,
            'role'    => $m->role,
            'status'  => $m->status,
        ]);

        $owner = [
            'id'      => null,
            'user_id' => $project->user_id,
            'name'    => $project->user->name,
            'email'   => $project->user->email,
            'role'    => 'owner',
            'status'  => 'accepted',
        ];

        return response()->json([
            'success' => true,
            'data'    => collect([$owner])->merge($members)->values(),
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role'  => ['required', 'in:editor,viewer'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user->id === $project->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن دعوة صاحب المشروع نفسه.',
            ], 422);
        }

        $existing = $project->members()->where('user_id', $user->id)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم عضو بالفعل في هذا المشروع.',
            ], 422);
        }

        $member = ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => $user->id,
            'role'       => $validated['role'],
            'status'     => 'pending',
        ]);

        // ⭐ إشعار المستخدم المدعو
        $user->notify(
            new ProjectInvitationNotification(
                $project,
                $request->user()->name,
                $validated['role'],
                $member->id
            )
        );

        return response()->json([
            'success' => true,
            'message' => "تمت إضافة {$user->name}",
            'data'    => [
                'id'      => $member->id,
                'user_id' => $user->id,
                'name'    => $user->name,
                'email'   => $user->email,
                'role'    => $member->role,
                'status'  => $member->status,
            ],
        ], 201);
    }

    public function update(Request $request, Project $project, ProjectMember $member): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        if ($member->project_id !== $project->id) {
            abort(404);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:editor,viewer'],
        ]);

        $member->update(['role' => $validated['role']]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الدور',
            'data'    => [
                'id'   => $member->id,
                'role' => $member->role,
            ],
        ]);
    }

    public function destroy(Request $request, Project $project, ProjectMember $member): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        if ($member->project_id !== $project->id) {
            abort(404);
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'تمت إزالة العضو',
        ]);
    }

    public function accept(Request $request, Project $project, ProjectMember $member): JsonResponse
    {
        if ($member->project_id !== $project->id) {
            abort(404);
        }

        if ($member->user_id !== $request->user()->id) {
            abort(403, 'غير مصرح');
        }

        if ($member->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذه الدعوة لم تعد معلّقة.',
            ], 422);
        }

        $member->update(['status' => 'accepted']);

        // إشعار للمالك
        $project->user->notify(new MemberAcceptedNotification(
            $project,
            $request->user()->name
        ));

        return response()->json([
            'success' => true,
            'message' => "انضممت إلى {$project->name}",
            'data'    => ['project_id' => $project->id],
        ]);
    }

    public function reject(Request $request, Project $project, ProjectMember $member): JsonResponse
    {
        if ($member->project_id !== $project->id) {
            abort(404);
        }

        if ($member->user_id !== $request->user()->id) {
            abort(403, 'غير مصرح');
        }

        if ($member->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذه الدعوة لم تعد معلّقة.',
            ], 422);
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم رفض الدعوة.',
        ]);
    }
}