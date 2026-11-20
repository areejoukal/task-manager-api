<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tags = Tag::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return TagResource::collection($tags);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $exists = Tag::where('user_id', $request->user()->id)
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد تاج بهذا الاسم.',
            ], 422);
        }

        $tag = Tag::create([
            'user_id' => $request->user()->id,
            'name'    => $validated['name'],
            'color'   => $validated['color'] ?? '#6366f1',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء التاج',
            'data'    => new TagResource($tag),
        ], 201);
    }

    public function show(Request $request, Tag $tag): JsonResponse
    {
        if ($tag->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'data'    => new TagResource($tag),
        ]);
    }

    public function update(Request $request, Tag $tag): JsonResponse
    {
        if ($tag->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $tag->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التاج',
            'data'    => new TagResource($tag),
        ]);
    }

    public function destroy(Request $request, Tag $tag): JsonResponse
    {
        if ($tag->user_id !== $request->user()->id) {
            abort(403);
        }

        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التاج',
        ]);
    }
}