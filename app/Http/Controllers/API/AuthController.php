<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseController
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'token' => $user->createToken('api-token')->plainTextToken,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "email" => 'required',
            "password" => 'required',
        ], []);



        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email',  $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $user->photo = Storage::disk('public')->url($user->photo);
                $success['token'] =  $user->createToken('LaravelAuthApp')->accessToken;
                $success['user'] =  $user;

                return $this->sendResponse($success, 'User login successfully.');
            } else {

                return $this->sendError('Unauthorised.', ['error' => 'Unauthorised'], 401);
            }
        } else {

            return $this->sendError('Unauthorised.', ['error' => 'Unauthorised']);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
