<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Notifications\NewCommentNotification;

class CommentController extends Controller
{
    /**
     * جلب تعليقات مهمة (الأقدم أولاً).
     */
    public function index(Request $request, Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', $task);

        $comments = $task->comments()
            ->with('user')
            ->orderBy('created_at')
            ->get();

        return CommentResource::collection($comments);
    }

    /**
     * إضافة تعليق على مهمة.
     */
   

public function store(StoreCommentRequest $request, Task $task): JsonResponse
{
    $this->authorize('view', $task);

    $comment = $task->comments()->create([
        'user_id' => $request->user()->id,
        'body'    => $request->validated()['body'],
    ]);

    $comment->load('user');

    // ⭐ إشعار صاحب المهمة (إن لم يكن هو المُعلِّق)
    if ($task->user_id !== $request->user()->id) {
        $task->user->notify(
            new NewCommentNotification($comment, $task, $request->user()->name)
        );
    }

    return response()->json([
        'success' => true,
        'message' => 'تم إضافة التعليق',
        'data'    => new CommentResource($comment),
    ], 201);
}

    /**
     * حذف تعليق (فقط صاحبه).
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التعليق',
        ]);
    }
}