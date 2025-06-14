<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class UnityLoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = Str::uuid()->toString();

        $userId = $user->id;
        $username = $user->name;

        // Checa se há token antigo
        $oldToken = Redis::get("user_token:$userId");
        if ($oldToken) {
            Redis::del("session:$oldToken");
        }

        // Salva novo token com os dados (sem expiração)
        Redis::set("session:$token", json_encode([
            'user_id' => $userId,
            'username' => $username,
            'battle_instance_id' => null,
            'status' => 'alive',
        ]));

        // Atualiza índice do usuário com novo token
        Redis::set("user_token:$userId", $token);

        return response()->json(['token' => $token]);
    }
}
