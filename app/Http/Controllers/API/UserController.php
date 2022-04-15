<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Rules\Password;
use PhpParser\Node\Stmt\TryCatch;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'     => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'unique:users'],
                'email'    => ['required', 'string', 'max:255', 'unique:users', 'email'],
                'phone'    => ['nullable', 'string', 'max:255'],
                'password' => ['required', 'string', new Password],
            ]);

            $validated['password'] = Hash::make($request->password);

            $user = User::create($validated);

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success(
                [
                    'access_token' => $tokenResult,
                    'token_type'   => 'Bearer',
                    'user'         => $user
                ],
                'User registered successfully'
            );
        } catch (ValidationException $e) {
            return ResponseFormatter::error(
                [
                    'message'    => 'Something went wrong',
                    'error'      => $e,
                    'validation' => $e->errors()
                ],
                'Registration Failed',
                500
            );
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email'    => ['required', 'email'],
                'password' => ['required']
            ]);

            $credentials = request(['email', 'password']);

            if (!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ], 'Authentication Failed', 401);
            }

            $user = User::where('email', $request->email)->first();

            if (!Hash::check($request->password, $user->password)) {
                throw new \Exception("Invalid Credentials");
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success(
                [
                    'access_token' => $tokenResult,
                    'token_type'   => 'Bearer',
                    'user'         => $user
                ],
                'Login success.'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::error(
                [
                    'message'    => 'Something went wrong',
                    'error'      => $e,
                ],
                'Authentication Failed',
                500
            );
        }
    }

    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(), 'Data profile');
    }

    public function updateProfile(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();
        $user->update($data);

        return ResponseFormatter::success($user, 'Profile updated.');
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken()->delete();

        return ResponseFormatter::success($token, 'Token revoked');
    }
}
