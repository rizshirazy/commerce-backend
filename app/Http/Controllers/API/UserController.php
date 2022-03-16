<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Rules\Password;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'     => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'unique:users'],
                'email'    => ['required', 'string', 'max:255', 'unique:users', 'email'],
                'phone'    => ['required', 'string', 'max:255'],
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
                'Authentication Failed',
                500
            );
        }
    }
}
