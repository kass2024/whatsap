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
            ->where('role', UserRole::Agent)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json(['data' => $rows]);
    }
}
