<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClientAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $request->input('login');
        $password = $request->input('password');

        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'cpf';
        if ($field === 'cpf') {
            $login = preg_replace('/\D/', '', $login);
        }

        // Busca o client
        $client = $field === 'cpf'
            ? Client::where('cpf', $login)->first()
            : Client::whereHas('user', fn($q) => $q->where('email', $login))->first();

        if (!$client || !Hash::check($password, $client->user->password)) {
            return back()->withErrors(['login' => 'Credenciais inválidas']);
        }

        Auth::login($client->user); // login no guard default (web)

        if ($client->user->hasRole('Cliente')) {
            return redirect()->route('initial-page');
        } else {
            return redirect()->route('filament.admin.pages.dashboard');
        }

    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'cpf'      => 'required|string|unique:clients,cpf',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $cpf = $request->cpf;

        DB::beginTransaction();
        try {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $cliente = Client::create([
                'user_id' => $user->id,
                'cpf'     => $cpf,
                'email'   => $request->email,
                'status'  => 1,
            ]);

            DB::commit();

            Auth::login($user);

            if (!$user->hasRole('Cliente')) {
                $user->syncRoles(['Cliente']);
            }

            if ($user->hasRole('Cliente')) {
                return redirect()->route('initial-page');
            } elseif ($user->hasRole('Admin')) {
                return redirect()->route('filament.admin.pages.dashboard');
            }


        } catch (\Throwable $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['register' => 'Erro ao criar conta. Tente novamente.']);
        }
    }

}

