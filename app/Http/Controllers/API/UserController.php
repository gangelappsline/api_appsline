<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserController extends BaseController
{
    public function index()
    {
        $users = User::all();
        return UserResource::collection($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string',
            'password' => 'required|string|min:6',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $validated['password'] = bcrypt($validated['password']);

        $photo = $request->file('avatar');
        unset($validated['avatar']);

        $user = User::create($validated);

        if ($photo) {
            $ext = $photo->extension();
            $path = "users/{$user->id}/photo.$ext";
            Storage::disk('public')->putFileAs("users/{$user->id}", $photo, "photo.$ext");
            $user->photo = $path;
            $user->save();
        }

        return new UserResource($user);
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $path = "users/{$user->id}";
            if (!Storage::disk('public')->exists($path)) {
                Storage::disk('public')->makeDirectory($path);
            }
            $name = str_replace([" ", "/", "@"], '_', $file->getClientOriginalName()) . "." . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs($path, $file, $name);
            DB::table('users')->where('id', $user->id)->update(['photo' => $path . '/' . $name]);
        }

        unset($validated['avatar']);
        $user->update($validated);

        return new UserResource($user);
    }

    public function updateAvatar(Request $request, User $user){
        $validated = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $path = "users/{$user->id}";
            if (!Storage::disk('public')->exists($path)) {
                Storage::disk('public')->makeDirectory($path);
            }
            $name = str_replace([" ", "/", "@"], '_', $file->getClientOriginalName()) . "." . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs($path, $file, $name);
            DB::table('users')->where('id', $user->id)->update(['photo' => $path . '/' . $name]);
        }

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }
}
