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

        if ($user->isCliente()) {
            $query->where('owner_id', $user->id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }

        return $this->success(PetResource::collection($query->orderBy('name')->get()));
    }



    public function show($id)
    {
        $pet = Pet::with('owner')->findOrFail($id);
        return $this->success(new PetResource($pet));
    }

    public function store(PetRequest $request)
    {
        $data = $request->validated();

        // Si es cliente, el owner_id siempre es él mismo
        if (Auth::user()->isCliente()) {
            $data['owner_id'] = Auth::id();
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
        if (Auth::user()->isCliente()) {
        $total = Pet::where('owner_id', Auth::id())->count();
        if ($total >= 8) {
        return $this->error('Has alcanzado el límite de 8 mascotas.', 422);
             }
        }
    }



    public function update(PetRequest $request, $id)
    {
        $pet  = Pet::findOrFail($id);
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

        if ($pet->photo_path) {
            Storage::disk('public')->delete($pet->photo_path);
        }

        $pet->delete();

        return $this->success(null, 'Mascota eliminada exitosamente');
    }
}
