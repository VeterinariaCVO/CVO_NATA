<?php

namespace App\Http\Controllers;

use App\Http\Requests\PetRequest;
use App\Http\Resources\PetResource;
use App\Http\Traits\ApiResponse;
use App\Models\Pet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PetController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user  = Auth::user();
        $query = Pet::with('owner');

        // Cliente solo ve sus propias mascotas, ignorando cualquier filtro externo
        if ($user->isCliente()) {
            $query->where('owner_id', $user->id);
        }

        // Solo admin y recepcionista pueden filtrar por dueño
        if ($request->filled('owner_id') && in_array($user->role_id, [1, 2])) {
            $query->where('owner_id', $request->owner_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        return $this->success(PetResource::collection($query->orderBy('name')->get()));
    }

    public function show($id)
    {
        $pet = Pet::with('owner')->findOrFail($id);

        if (Auth::user()->isCliente() && $pet->owner_id !== Auth::id()) {
            return $this->error('No tienes permiso para ver esta mascota.', 403);
        }

        return $this->success(new PetResource($pet));
    }

    public function store(PetRequest $request)
    {
        $data = $request->validated();

        if (Auth::user()->isCliente()) {
            $data['owner_id'] = Auth::id();

            $total = Pet::where('owner_id', Auth::id())->count();
            if ($total >= 8) {
                return $this->error('Has alcanzado el límite de 8 mascotas.', 422);
            }
        }

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('pets', 'public');
        }

        $pet = Pet::create($data);

        return $this->success(
            new PetResource($pet->load('owner')),
            'Mascota registrada exitosamente',
            201
        );
    }

    public function update(PetRequest $request, $id)
    {
        $pet = Pet::findOrFail($id);

        if (Auth::user()->isCliente() && $pet->owner_id !== Auth::id()) {
            return $this->error('No tienes permiso para modificar esta mascota.', 403);
        }

        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($pet->photo_path) {
                Storage::disk('public')->delete($pet->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('pets', 'public');
        }

        $pet->update($data);

        return $this->success(
            new PetResource($pet->load('owner')),
            'Mascota actualizada exitosamente'
        );
    }

    public function destroy($id)
    {
        $pet = Pet::findOrFail($id);

        if (Auth::user()->isCliente() && $pet->owner_id !== Auth::id()) {
            return $this->error('No tienes permiso para eliminar esta mascota.', 403);
        }

        if ($pet->photo_path) {
            Storage::disk('public')->delete($pet->photo_path);
        }

        $pet->delete();

        return $this->success(null, 'Mascota eliminada exitosamente');
    }
}
