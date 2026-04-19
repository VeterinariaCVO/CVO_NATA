<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Notifications\UserRegistered;

class UserController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        // 1. Empezamos a armar la consulta (con su relación role)
        $query = User::with('role');

        // 2. Si Vue nos manda un role_id por la URL, filtramos por ese rol
        if ($request->has('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        // 3. Ejecutamos la consulta: solo los activos, ordenados por nombre
        $users = $query->where('active', true)
                       ->orderBy('name')
                       ->get();

        // 4. Devolvemos la respuesta usando tu Trait y tu Resource
        return $this->success(UserResource::collection($users));
    }

    public function store(UserRequest $request)
    {
        $data             = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['active']   = $data['active'] ?? true;

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')
                ->store('profile_photos', 'public');
        }

        $user = User::create($data);

        $user->notify(new UserRegistered($user));

        return $this->success(
            new UserResource($user->load('role')),
            'Usuario creado correctamente',
            201
        );
    }

    public function show(string $id)
    {
        $user = User::with('role')->findOrFail($id);
        return $this->success(new UserResource($user));
    }

    public function update(UserRequest $request, string $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')
                ->store('profile_photos', 'public');
        }

        if ($request->input('remove_photo') === '1') {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $data['profile_photo'] = null;
        }

        $user->update($data);

        return $this->success(
            new UserResource($user->load('role')),
            'Usuario actualizado correctamente'
        );
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $user->delete();

        return $this->success(null, 'Usuario eliminado correctamente');
    }

    public function employees()
    {
        $employees = User::with('role')
            ->whereIn('role_id', [2, 4])
            ->orderBy('name')
            ->get();
        return $this->success(UserResource::collection($employees));
    }

    public function showEmployee(string $id)
    {
        $employee = User::with('role')
            ->whereIn('role_id', [2, 4])
            ->findOrFail($id);
        return $this->success(new UserResource($employee));
    }

    public function clients()
    {
        $clients = User::with('role')
            ->where('role_id', 3)
            ->orderBy('name')
            ->get();
        return $this->success(UserResource::collection($clients));
    }

    public function showClient(string $id)
    {
        $client = User::with(['role', 'pets'])
            ->where('role_id', 3)
            ->findOrFail($id);

        return $this->success([
            'client' => new UserResource($client),
            'pets'   => $client->pets,
        ]);
    }
}
