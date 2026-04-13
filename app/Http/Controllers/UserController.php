<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Notifications\UserRegistered;
use App\Notifications\NewClientRegistered;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;
    public function index()
    {
        $users = User::with('role')->orderBy('name')->get();
        return $this->success(UserResource::collection($users));
    }

    public function store(UserRequest $request)
{
    $user = Auth::user();
    $data = $request->validated();

    if ($user->role_id === 2) {
        $data['role_id'] = 3;
    }

    $data['password'] = Hash::make($data['password']);
    $data['active']   = true;

    $newUser = User::create($data);


    $newUser->notify(new UserRegistered($newUser));

    if ($newUser->role_id === 3) {
        User::whereIn('role_id', [1, 2])
            ->where('active', true)
            ->where('id', '!=', $user->id)
            ->get()
            ->each(fn($u) => $u->notify(new NewClientRegistered($newUser)));
    }

    return $this->success(
        new UserResource($newUser),
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
