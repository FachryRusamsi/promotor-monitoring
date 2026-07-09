<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'regions' => Region::all(),
            'areas' => Area::all(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],

            'region_id' => ['required', 'exists:regions,id'],
            'area_id' => ['required', 'exists:areas,id'],

            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $promotorRole = Role::where('name', 'promotor')->firstOrFail();

        $user = User::create([
            'role_id' => $promotorRole->id,

            'region_id' => $request->region_id,
            'area_id' => $request->area_id,

            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,

            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('promotor.dashboard');
    }
}