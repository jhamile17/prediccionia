<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Mostrar formulario de login.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Procesar login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => [
                'required',
                'email',
            ],
            'password' => [
                'required',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Intentar autenticar
        |--------------------------------------------------------------------------
        */

        if (!Auth::attempt($credentials)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'El correo o la contraseña son incorrectos.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Regenerar sesión
        |--------------------------------------------------------------------------
        */

        $request->session()->regenerate();

        $usuario = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Validar que el usuario esté activo
        |--------------------------------------------------------------------------
        */

        if ($usuario->rol && !$usuario->rol->activo) {

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'El usuario no está activo.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validar que sea administrador
        |--------------------------------------------------------------------------
        */

        if (
            !$usuario->rol ||
            !in_array(
                strtoupper(trim($usuario->rol->nombre)),
                ['ADMIN', 'ADMINISTRADOR']
            )
        ) {

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'No tienes permisos para acceder al panel administrativo.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Login correcto
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('dashboard')
            ->with('success', 'Bienvenido, ' . $usuario->name . '.');
    }

    /**
     * Cerrar sesión.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Sesión cerrada correctamente.');
    }
}