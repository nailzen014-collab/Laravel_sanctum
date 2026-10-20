<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'confirm_password.required' => 'Konfirmasi password wajib diisi.',
            'confirm_password.same' => 'Konfirmasi password tidak cocok.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        $success['token'] = $user->createToken('auth_token')->plainTextToken;
        $success['name'] = $user->name;

        return response()->json([
            'status' => true,
            'message' => 'User registered successfully',
            'data' => $success,
        ], 201);
    }

    public function login(Request $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $success['token'] = $user->createToken('auth_token')->plainTextToken;
            $success['name'] = $user->name;

            return response()->json([
                'status' => true,
                'message' => 'User logged in successfully',
                'data' => $success,
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
                'data' => null,
            ], 401);
        }
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'message' => 'Data user berhasil diambil',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'jumlah_catatan' => $user->catatans()->count(),
                'created_at' => $user->created_at,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'User logged out successfully',
            'data' => null,
        ], 200);
    }
}
