<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;

class SimpleAuthController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function sendLoginCode(Request $request)
    {
        $email = $request->input('email');
        
        // Simple validation
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withInput()->withErrors(['email' => 'Please enter a valid email address']);
        }

        try {
            // Generate 6-digit code (always generate, even if user doesn't exist for security)
            $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Check if user exists in Supabase
            $user = null;
            $userExists = false;
            
            try {
                $user = $this->supabase->query('users', '*', ['email' => $email]);
                $userExists = !empty($user) && is_array($user) && count($user) > 0;
            } catch (\Exception $e) {
                // Supabase query failed, but continue to avoid revealing database issues
            }
            
            // Store in session temporarily (whether user exists or not for security)
            Session::put('login_email', $email);
            Session::put('login_code', $code);
            Session::put('code_expires', time() + 600); // 10 minutes
            Session::put('user_exists', $userExists);
            Session::put('user_data', $userExists && isset($user[0]) ? $user[0] : ['email' => $email]);

            // Send email via Laravel Mail with Resend
            // Temporarily send to all emails for testing - remove this condition later
            try {
                Mail::send([], [], function ($message) use ($email, $code) {
                    $message->to($email)
                            ->subject('Your Connectly Login Code')
                            ->html("
                                <h2>Your Login Code</h2>
                                <p>Your login code is: <strong style='font-size: 24px; color: #4f46e5;'>{$code}</strong></p>
                                <p>This code expires in 10 minutes.</p>
                                <p><small>User exists: " . ($userExists ? 'Yes' : 'No') . "</small></p>
                            ");
                });
            } catch (\Exception $mailError) {
                return back()->withInput()->withErrors(['email' => 'Failed to send email: ' . $mailError->getMessage()]);
            }

            // Always show success message for security (don't reveal if user exists)
            return redirect()->route('auth.verify')->with('success', 'If this email is registered, a login code has been sent!');
            
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['email' => 'Failed to send code. Please try again. Error: ' . $e->getMessage()]);
        }
    }

    public function showVerifyCode()
    {
        if (!Session::has('login_email')) {
            return redirect()->route('login');
        }
        
        return view('auth.verify');
    }

    public function verifyCode(Request $request)
    {
        $code = $request->input('code');
        $sessionCode = Session::get('login_code');
        $expires = Session::get('code_expires');
        $email = Session::get('login_email');
        
        if (!$email || !$sessionCode || !$expires) {
            return redirect()->route('login')->withErrors(['error' => 'Session expired']);
        }
        
        if (time() > $expires) {
            Session::forget(['login_code', 'code_expires']);
            return back()->withErrors(['code' => 'Code has expired']);
        }
        
        if ($code !== $sessionCode) {
            return back()->withErrors(['code' => 'Invalid code']);
        }
        
        // Check if user actually exists before logging them in
        $userExists = Session::get('user_exists', false);
        // Temporarily allow login even if user doesn't exist for testing
        // Remove this condition later and uncomment below
        /*
        if (!$userExists) {
            Session::flush();
            return redirect()->route('login')->withErrors(['email' => 'Email not found. Please register first.']);
        }
        */

        // Login successful
        Session::put('authenticated', true);
        Session::put('user_email', $email);
        Session::forget(['login_code', 'code_expires', 'user_exists']);
        
        return redirect()->route('dashboard');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $name = $request->input('name');
        $email = $request->input('email');
        $company = $request->input('company');
        
        // Simple validation
        if (!$name) {
            return back()->withInput()->withErrors(['name' => 'Name is required']);
        }
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withInput()->withErrors(['email' => 'Please enter a valid email address']);
        }

        try {
            // Check if user already exists in Supabase
            $existingUser = $this->supabase->query('users', '*', ['email' => $email]);
            if (!empty($existingUser)) {
                return back()->withInput()->withErrors(['email' => 'Email already registered. Please login instead.']);
            }

            // Store user in Supabase
            $userData = [
                'name' => $name,
                'email' => $email,
                'company' => $company,
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];

            $result = $this->supabase->insert('users', $userData);
            
            if (!$result) {
                return back()->withInput()->withErrors(['email' => 'Failed to create account. Please try again.']);
            }

            // Send welcome email
            Mail::send([], [], function ($message) use ($email, $name) {
                $message->to($email)
                        ->subject('Welcome to Connectly!')
                        ->html("
                            <h2>Welcome to Connectly!</h2>
                            <p>Hi {$name},</p>
                            <p>Welcome to Connectly - your modern CRM solution!</p>
                            <p>You can now login to start managing your customers.</p>
                        ");
            });

            return redirect()->route('login')->with('success', 'Account created! Check your email and then login.');
            
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['email' => 'Registration failed. Please try again. Error: ' . $e->getMessage()]);
        }
    }

    public function dashboard()
    {
        if (!Session::get('authenticated')) {
            return redirect()->route('login');
        }
        
        $userData = Session::get('user_data');
        
        return view('auth.dashboard', [
            'user_email' => Session::get('user_email'),
            'user_name' => $userData['name'] ?? 'User',
            'user_company' => $userData['company'] ?? null
        ]);
    }

    public function logout()
    {
        Session::flush();
        return redirect('/')->with('success', 'Logged out successfully');
    }
}