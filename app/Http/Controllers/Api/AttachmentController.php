<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    /**
     * جلب مرفقات مهمة.
     */
    public function index(Request $request, Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', $task);

        $attachments = $task->attachments()->with('user')->get();

        return AttachmentResource::collection($attachments);
    }

    /**
     * رفع مرفق على مهمة.
     */
    public function store(StoreAttachmentRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // تخزين في storage/app/public/attachments/{task_id}/
        $extension = $file->getClientOriginalExtension();
        $storedName = Str::uuid() . '.' . $extension;
        $path = $file->storeAs("attachments/{$task->id}", $storedName, 'public');

        $attachment = $task->attachments()->create([
            'user_id'    => $request->user()->id,
            'filename'   => $filename,
            'path'       => $path,
            'mime_type'  => $mimeType,
            'size'       => $size,
        ]);

        $attachment->load('user');

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الملف بنجاح',
            'data'    => new AttachmentResource($attachment),
        ], 201);
    }

    /**
     * حذف مرفق.
     */
    public function destroy(Request $request, Attachment $attachment): JsonResponse
    {
        $this->authorize('update', $attachment->task);

        // حذف الملف من storage
        if (Storage::disk('public')->exists($attachment->path)) {
            Storage::disk('public')->delete($attachment->path);
        }

        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المرفق',
        ]);
    }
}