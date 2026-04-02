<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AgentController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = User::query()
            ->whereIn('role', [UserRole::Agent, UserRole::Admin])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $data = $rows->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role->value,
        ]);

        return response()->json(['data' => $data]);
    }
}
