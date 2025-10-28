<?php

namespace App\Http\Controllers\API;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends BaseController
{
    public function index()
    {
        return Post::where('user_id', auth()->id())->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post = Post::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        return response()->json($post, 201);
    }

    public function show($id)
    {
        $post = Post::find($id);
        return $this->sendResponse($post, "Post");
    }

    public function update(Request $request,  $id)
    {

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
        ]);

    }

    public function destroy($id)
    {
    }
}