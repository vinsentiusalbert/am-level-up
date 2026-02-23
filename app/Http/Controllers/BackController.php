<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;

class BackController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi form
        $request->validate([
            'email' => [
                'required',
                'email',
                // 'regex:/^[a-zA-Z0-9._%+-]+@telkomsel\.co\.id$/', // hanya email @telkomsel.co.id
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', // semua domain
            ],
            'password' => 'required',
        ], [
            'email.regex' => 'Email harus menggunakan domain @telkomsel.co.id',
        ]);
        
        // dd('asd');
        // 2. Coba login
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();

            if (($user->role ?? null) !== 'b2b') {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Akses login hanya untuk user role b2b.',
                ])->withInput();
            }

            $request->session()->regenerate();
            
            return redirect()->route('b2b.clients.index');
            
        }

        // 6. Kalau gagal login
        return back()->withErrors([
            'email' => 'Email atau Password Anda salah.',
        ])->withInput();
    }
    public function logout()
    {
        // Menghapus sesi dan logout
        Session::flush();
        Auth::logout();
        // Redirect ke halaman utama
        return redirect('/');
    }
}
