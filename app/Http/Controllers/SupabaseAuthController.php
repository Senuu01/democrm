<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SupabaseAuthController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function showLogin()
    {
        return view('auth.simple-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $response = $this->supabase->signIn($request->email, $request->password);
            
            if (isset($response['access_token'])) {
                Session::put('supabase_token', $response['access_token']);
                Session::put('user_email', $request->email);
                Session::put('authenticated', true);
                
                return redirect()->intended('/dashboard');
            } else {
                return back()->withErrors(['email' => 'Invalid credentials']);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Login failed: ' . $e->getMessage()]);
        }
    }

    public function showRegister()
    {
        return view('auth.simple-register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $response = $this->supabase->signUp($request->email, $request->password);
            
            if (isset($response['user'])) {
                return redirect()->route('login')->with('success', 'Registration successful! Please login.');
            } else {
                return back()->withErrors(['email' => 'Registration failed']);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Registration failed: ' . $e->getMessage()]);
        }
    }

    public function logout()
    {
        Session::flush();
        return redirect('/');
    }

    public function dashboard()
    {
        if (!Session::get('authenticated')) {
            return redirect()->route('login');
        }
        
        return view('dashboard', [
            'user' => (object) ['email' => Session::get('user_email')]
        ]);
    }
}