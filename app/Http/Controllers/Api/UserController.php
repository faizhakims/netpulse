<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends BaseApiController
{
    public function __construct(private UserService $userService) {}

    public function index(Request $request)
    {
        if (!auth()->user()->can('manage users')) {
            return $this->error('Forbidden.', 403);
        }

        $query = User::with('roles');

        if ($search = $request->query('search')) {
            $query->where(fn($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            );
        }

        if ($role = $request->query('role')) {
            $query->role($role);
        }

        if ($status = $request->query('status')) {
            $query->where('is_active', $status === 'active');
        }

        $sort = $request->query('sort', 'name');
        $dir  = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $col  = ltrim($sort, '-');
        $allowed = ['name', 'email', 'created_at', 'last_login_at'];
        if (!in_array($col, $allowed)) $col = 'name';

        $query->orderBy($col, $dir);

        $users = $query->paginate((int) $request->query('per_page', 20));

        return $this->success([
            'items'        => UserResource::collection($users->items()),
            'total'        => $users->total(),
            'per_page'     => $users->perPage(),
            'current_page' => $users->currentPage(),
            'last_page'    => $users->lastPage(),
        ]);
    }

    public function show(int $id)
    {
        if (!auth()->user()->can('manage users')) {
            return $this->error('Forbidden.', 403);
        }

        $user = User::with('roles')->find($id);

        if (!$user) {
            return $this->error('User not found.', 404);
        }

        return $this->success(new UserResource($user));
    }
}
