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

        if (! Auth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user     = Auth::user();
        $userId   = $user->id;
        $username = $user->name;
        $token    = Str::uuid()->toString();

        // Se havia um token antigo para esse usuário, remove a sessão antiga
        $oldToken = Redis::get("user_token:{$userId}");
        if ($oldToken) {
            Redis::del("session:{$oldToken}");
        }

        // 1) Grava no hash "session:{token}" todos os campos que vamos usar
        Redis::hset("session:{$token}", [
            'user_id'            => $userId,
            'username'           => $username,
            'battle_instance_id' => null,
            'status'             => 'alive',
        ]);

        // 2) (Opcional) define TTL para expirar a sessão em 24h
        Redis::expire("session:{$token}", 60 * 60 * 24);

        // 3) Atualiza índice rápido de lookup de token por usuário
        Redis::set("user_token:{$userId}", $token);

        return response()->json(['token' => $token]);
    }
}
