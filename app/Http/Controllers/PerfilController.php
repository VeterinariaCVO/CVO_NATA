<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PerfilController extends Controller
{
    public function show()
    {
        return response()->json([
            'success' => true,
            'data'    => Auth::user()
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'             => 'required|string|min:2|max:100',
            'email'            => 'required|email|unique:users,email,' . $user->id,
            'phone'            => 'nullable|digits:10',
            'address'          => 'nullable|string|max:255',
            'gender'           => 'nullable|in:masculino,femenino',
            'birth_date'       => 'nullable|date|before:today',
            'profile_photo'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_photo'     => 'nullable',
            'current_password' => 'nullable|required_with:password',
            'password'         => 'nullable|min:8|confirmed',
        ]);

        $user->name       = $request->name;
        $user->email      = $request->email;
        $user->phone      = $request->phone;
        $user->address    = $request->address;
        $user->gender     = $request->gender;
        $user->birth_date = $request->birth_date;

        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'errors'  => ['current_password' => ['La contraseña actual es incorrecta.']]
                ], 422);
            }
            $user->password = Hash::make($request->password);
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $user->profile_photo = $request->file('profile_photo')->store('profiles', 'public');
        }

        if ($request->remove_photo) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $user->profile_photo = null;
        }

        $user->save();

        return response()->json([
        'success' => true,
        'message' => 'Perfil actualizado correctamente.',
        'data'    => new \App\Http\Resources\UserResource($user)
    ]);
    }

    public function destroy()
    {
        $user = Auth::user();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta eliminada.'
        ]);
    }
}
