<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServiceResource;
use App\Http\Traits\ApiResponse;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(
            ServiceResource::collection(Service::where('active', true)->orderBy('name')->get())
        );
    }

    public function indexAdmin()
    {
        return $this->success(
            ServiceResource::collection(Service::orderBy('name')->get())
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|unique:services,name',
            'description'      => 'nullable|string',
            'price'            => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:5',
            'active'           => 'boolean',
        ]);

        $service = Service::create($data);

        return $this->success(new ServiceResource($service), 'Servicio creado', 201);
    }

    public function show($id)
    {
        return $this->success(new ServiceResource(Service::findOrFail($id)));
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $data = $request->validate([
            'name'             => 'sometimes|string|unique:services,name,' . $id,
            'description'      => 'nullable|string',
            'price'            => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:5',
            'active'           => 'boolean',
        ]);

        $service->update($data);

        return $this->success(new ServiceResource($service), 'Servicio actualizado');
    }

    public function destroy($id)
    {
        Service::findOrFail($id)->delete();
        return $this->success(null, 'Servicio eliminado');
    }
}
