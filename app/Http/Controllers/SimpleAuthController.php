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
            // Generate 6-digit code
            $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Check if user exists in Supabase
            $user = null;
            $userExists = false;
            
            try {
                $user = $this->supabase->query('users', '*', ['email' => $email]);
                \Log::info('Supabase query response:', ['user' => $user, 'type' => gettype($user)]);
                
                $userExists = !empty($user) && is_array($user) && count($user) > 0;
                $userData = null;
                
                if ($userExists && isset($user[0])) {
                    $userData = $user[0];
                } elseif ($userExists && is_array($user)) {
                    // Handle case where user data is directly in the response
                    $userData = $user;
                } else {
                    return back()->withInput()->withErrors(['email' => 'Email not found. Please register first.']);
                }
            } catch (\Exception $e) {
                \Log::error('Supabase query error:', ['error' => $e->getMessage()]);
                return back()->withInput()->withErrors(['email' => 'Database error. Please try again later.']);
            }
            
            // Store in session temporarily
            Session::put('login_email', $email);
            Session::put('login_code', $code);
            Session::put('code_expires', time() + 600); // 10 minutes
            Session::put('user_data', $userData);

            // Send email via Laravel Mail with Resend
            try {
                Mail::send([], [], function ($message) use ($email, $code) {
                    $message->to($email)
                            ->subject('Your Connectly Login Code')
                            ->html("
                                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                                    <h2 style='color: #4f46e5; text-align: center;'>Your Login Code</h2>
                                    <div style='background: #f8fafc; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0;'>
                                        <p style='font-size: 18px; color: #64748b; margin-bottom: 10px;'>Your login code is:</p>
                                        <p style='font-size: 32px; font-weight: bold; color: #4f46e5; letter-spacing: 4px; margin: 10px 0;'>{$code}</p>
                                    </div>
                                    <p style='color: #64748b; text-align: center;'>This code expires in 10 minutes.</p>
                                    <p style='color: #64748b; text-align: center; font-size: 14px;'>If you didn't request this code, please ignore this email.</p>
                                </div>
                            ");
                });
            } catch (\Exception $mailError) {
                return back()->withInput()->withErrors(['email' => 'Failed to send email: ' . $mailError->getMessage()]);
            }

            return redirect()->route('auth.verify')->with('success', 'Login code sent to your email!');
            
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
        
        // Login successful
        Session::put('authenticated', true);
        Session::put('user_email', $email);
        Session::forget(['login_code', 'code_expires']);
        
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
            \Log::info('Registration check:', ['existingUser' => $existingUser, 'email' => $email]);
            
            if (!empty($existingUser) && is_array($existingUser) && count($existingUser) > 0) {
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
            \Log::info('Supabase insert result:', ['result' => $result]);
            
            if (!$result || (is_array($result) && isset($result['error']))) {
                $errorMessage = is_array($result) && isset($result['error']) ? $result['error']['message'] : 'Unknown error';
                return back()->withInput()->withErrors(['email' => 'Failed to create account: ' . $errorMessage]);
            }

            // Send welcome email
            try {
                Mail::send([], [], function ($message) use ($email, $name) {
                    $message->to($email)
                            ->subject('Welcome to Connectly!')
                            ->html("
                                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                                    <h2 style='color: #4f46e5; text-align: center;'>Welcome to Connectly!</h2>
                                    <div style='background: #f8fafc; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                                        <p style='font-size: 18px; color: #1f2937;'>Hi {$name},</p>
                                        <p style='color: #64748b;'>Welcome to Connectly - your modern CRM solution!</p>
                                        <p style='color: #64748b;'>You can now login to start managing your customers and grow your business.</p>
                                    </div>
                                    <div style='text-align: center; margin: 30px 0;'>
                                        <a href='" . route('login') . "' style='background: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>Login Now</a>
                                    </div>
                                    <p style='color: #64748b; text-align: center; font-size: 14px;'>Thank you for joining Connectly!</p>
                                </div>
                            ");
                });
            } catch (\Exception $mailError) {
                // Registration still succeeds even if welcome email fails
                \Log::error('Welcome email failed: ' . $mailError->getMessage());
            }

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