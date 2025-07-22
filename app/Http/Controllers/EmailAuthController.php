<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Resend;

class EmailAuthController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function showLogin()
    {
        return view('auth.email-login');
    }

    public function sendLoginCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        try {
            // Check if user exists in Supabase
            $users = $this->supabase->query('users', '*', ['email' => $request->email]);
            
            if (empty($users)) {
                return back()->withErrors(['email' => 'No account found with this email']);
            }

            // Generate 6-digit code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store code in Supabase with expiration
            $this->supabase->insert('login_codes', [
                'email' => $request->email,
                'code' => $code,
                'expires_at' => now()->addMinutes(10)->toISOString(),
                'used' => false
            ]);

            // Send code via Resend
            $resend = Resend::client(config('services.resend.key'));
            
            $resend->emails->send([
                'from' => config('mail.from.address'),
                'to' => [$request->email],
                'subject' => 'Your Connectly Login Code',
                'html' => view('emails.login-code', ['code' => $code])->render()
            ]);

            Session::put('login_email', $request->email);
            
            return redirect()->route('auth.verify-code')->with('success', 'Login code sent to your email!');
            
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Failed to send login code: ' . $e->getMessage()]);
        }
    }

    public function showVerifyCode()
    {
        if (!Session::has('login_email')) {
            return redirect()->route('auth.login');
        }
        
        return view('auth.verify-code');
    }

    public function verifyCode(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);
        
        $email = Session::get('login_email');
        if (!$email) {
            return redirect()->route('auth.login');
        }

        try {
            // Get valid codes from Supabase
            $codes = $this->supabase->query('login_codes', '*', [
                'email' => $email,
                'code' => $request->code,
                'used' => 'false'
            ]);

            if (empty($codes)) {
                return back()->withErrors(['code' => 'Invalid or expired code']);
            }

            $loginCode = $codes[0];
            
            // Check if code is expired
            if (now()->isAfter($loginCode['expires_at'])) {
                return back()->withErrors(['code' => 'Code has expired']);
            }

            // Mark code as used
            $this->supabase->update('login_codes', $loginCode['id'], ['used' => true]);

            // Get user data
            $users = $this->supabase->query('users', '*', ['email' => $email]);
            $user = $users[0];

            // Set session
            Session::put('authenticated', true);
            Session::put('user_id', $user['id']);
            Session::put('user_email', $user['email']);
            Session::put('user_name', $user['name']);

            return redirect()->route('dashboard');
            
        } catch (\Exception $e) {
            return back()->withErrors(['code' => 'Verification failed: ' . $e->getMessage()]);
        }
    }

    public function showRegister()
    {
        return view('auth.email-register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'company' => 'nullable|string|max:255'
        ]);

        try {
            // Check if user already exists
            $existingUsers = $this->supabase->query('users', '*', ['email' => $request->email]);
            
            if (!empty($existingUsers)) {
                return back()->withErrors(['email' => 'Email already registered']);
            }

            // Create user in Supabase
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'company' => $request->company,
                'created_at' => now()->toISOString()
            ];
            
            $result = $this->supabase->insert('users', $userData);
            
            if ($result) {
                // Send welcome email via Resend
                $resend = Resend::client(config('services.resend.key'));
                
                $resend->emails->send([
                    'from' => config('mail.from.address'),
                    'to' => [$request->email],
                    'subject' => 'Welcome to Connectly!',
                    'html' => view('emails.welcome', ['name' => $request->name])->render()
                ]);

                return redirect()->route('auth.login')->with('success', 'Account created! You can now login.');
            }
            
            return back()->withErrors(['email' => 'Registration failed']);
            
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
            return redirect()->route('auth.login');
        }
        
        $user = (object) [
            'id' => Session::get('user_id'),
            'name' => Session::get('user_name'),
            'email' => Session::get('user_email')
        ];
        
        return view('dashboard', compact('user'));
    }
}