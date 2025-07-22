<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class EmailPasswordAuthController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    // REGISTRATION FLOW

    public function showRegister()
    {
        return view('auth.email-register');
    }

    public function register(Request $request)
    {
        $name = trim($request->input('name'));
        $email = trim(strtolower($request->input('email')));
        $password = $request->input('password');
        $confirmPassword = $request->input('password_confirmation');
        $company = trim($request->input('company'));

        // Validation
        if (!$name) {
            return back()->withInput()->withErrors(['name' => 'Name is required']);
        }
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withInput()->withErrors(['email' => 'Please enter a valid email address']);
        }
        if (!$password || strlen($password) < 6) {
            return back()->withInput()->withErrors(['password' => 'Password must be at least 6 characters']);
        }
        if ($password !== $confirmPassword) {
            return back()->withInput()->withErrors(['password_confirmation' => 'Passwords do not match']);
        }

        try {
            // Check if user already exists
            $existingUser = $this->supabase->query('auth_users', '*', ['email' => $email]);
            
            if (!empty($existingUser) && is_array($existingUser) && count($existingUser) > 0) {
                return back()->withInput()->withErrors(['email' => 'Email already registered. Please login instead.']);
            }

            // Generate verification code
            $verificationCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $hashedPassword = Hash::make($password);

            // Store user in Supabase with unverified status
            $userData = [
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'company' => $company,
                'email_verified' => false,
                'verification_code' => $verificationCode,
                'verification_expires' => date('Y-m-d H:i:s', time() + 3600), // 1 hour
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];

            $result = $this->supabase->insert('auth_users', $userData);
            
            if (!$result || (is_array($result) && isset($result['error']))) {
                $errorMessage = is_array($result) && isset($result['error']) ? $result['error']['message'] : 'Registration failed';
                return back()->withInput()->withErrors(['email' => 'Registration failed: ' . $errorMessage]);
            }

            // Send verification email
            $this->sendVerificationEmail($email, $name, $verificationCode);

            return redirect()->route('auth.verify-email-form')
                ->with('success', 'Registration successful! Please check your email for verification code.')
                ->with('email', $email);

        } catch (\Exception $e) {
            \Log::error('Registration error: ' . $e->getMessage());
            return back()->withInput()->withErrors(['email' => 'Registration failed. Please try again.']);
        }
    }

    // EMAIL VERIFICATION

    public function showEmailVerification()
    {
        if (!session('email')) {
            return redirect()->route('register');
        }
        return view('auth.verify-email');
    }

    public function verifyEmail(Request $request)
    {
        $email = session('email');
        $code = trim($request->input('verification_code'));

        if (!$email || !$code) {
            return back()->withErrors(['verification_code' => 'Verification code is required']);
        }

        try {
            // Get user from database
            $user = $this->supabase->query('auth_users', '*', ['email' => $email]);
            
            if (empty($user) || !is_array($user) || count($user) === 0) {
                return back()->withErrors(['verification_code' => 'User not found']);
            }

            $userData = $user[0];

            // Check verification code and expiry
            if ($userData['verification_code'] !== $code) {
                return back()->withErrors(['verification_code' => 'Invalid verification code']);
            }

            if (strtotime($userData['verification_expires']) < time()) {
                return back()->withErrors(['verification_code' => 'Verification code has expired. Please request a new one.']);
            }

            // Mark email as verified
            $updateData = [
                'email_verified' => true,
                'verification_code' => null,
                'verification_expires' => null,
                'updated_at' => now()->toISOString()
            ];

            $result = $this->supabase->update('auth_users', $updateData, ['email' => $email]);

            if (!$result) {
                return back()->withErrors(['verification_code' => 'Verification failed. Please try again.']);
            }

            // Auto-login after verification
            Session::put('authenticated', true);
            Session::put('user_email', $email);
            Session::put('user_data', [
                'id' => $userData['id'],
                'name' => $userData['name'],
                'email' => $userData['email'],
                'company' => $userData['company']
            ]);

            return redirect()->route('dashboard')->with('success', 'Email verified successfully! Welcome to Connectly CRM.');

        } catch (\Exception $e) {
            \Log::error('Email verification error: ' . $e->getMessage());
            return back()->withErrors(['verification_code' => 'Verification failed. Please try again.']);
        }
    }

    // LOGIN FLOW

    public function showLogin()
    {
        return view('auth.email-login');
    }

    public function login(Request $request)
    {
        $email = trim(strtolower($request->input('email')));
        $password = $request->input('password');

        if (!$email || !$password) {
            return back()->withInput()->withErrors(['email' => 'Email and password are required']);
        }

        try {
            // Get user from database
            $user = $this->supabase->query('auth_users', '*', ['email' => $email]);
            
            if (empty($user) || !is_array($user) || count($user) === 0) {
                return back()->withInput()->withErrors(['email' => 'Invalid email or password']);
            }

            $userData = $user[0];

            // Check if email is verified
            if (!$userData['email_verified']) {
                return back()->withInput()->withErrors(['email' => 'Please verify your email address first']);
            }

            // Check password
            if (!Hash::check($password, $userData['password'])) {
                return back()->withInput()->withErrors(['email' => 'Invalid email or password']);
            }

            // Login successful
            Session::put('authenticated', true);
            Session::put('user_email', $email);
            Session::put('user_data', [
                'id' => $userData['id'],
                'name' => $userData['name'],
                'email' => $userData['email'],
                'company' => $userData['company']
            ]);

            return redirect()->route('dashboard')->with('success', 'Login successful!');

        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage());
            return back()->withInput()->withErrors(['email' => 'Login failed. Please try again.']);
        }
    }

    // FORGOT PASSWORD FLOW

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetCode(Request $request)
    {
        $email = trim(strtolower($request->input('email')));

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withInput()->withErrors(['email' => 'Please enter a valid email address']);
        }

        try {
            // Check if user exists
            $user = $this->supabase->query('auth_users', '*', ['email' => $email]);
            
            if (empty($user) || !is_array($user) || count($user) === 0) {
                return back()->withInput()->withErrors(['email' => 'Email not found']);
            }

            $userData = $user[0];

            // Generate reset code
            $resetCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            // Update user with reset code
            $updateData = [
                'reset_code' => $resetCode,
                'reset_code_expires' => date('Y-m-d H:i:s', time() + 1800), // 30 minutes
                'updated_at' => now()->toISOString()
            ];

            $result = $this->supabase->update('auth_users', $updateData, ['email' => $email]);

            if (!$result) {
                return back()->withInput()->withErrors(['email' => 'Failed to send reset code']);
            }

            // Send reset email
            $this->sendPasswordResetEmail($email, $userData['name'], $resetCode);

            return redirect()->route('auth.reset-password-form')
                ->with('success', 'Password reset code sent to your email!')
                ->with('email', $email);

        } catch (\Exception $e) {
            \Log::error('Password reset error: ' . $e->getMessage());
            return back()->withInput()->withErrors(['email' => 'Failed to send reset code. Please try again.']);
        }
    }

    public function showResetPassword()
    {
        if (!session('email')) {
            return redirect()->route('auth.forgot-password');
        }
        return view('auth.reset-password');
    }

    public function resetPassword(Request $request)
    {
        $email = session('email');
        $resetCode = trim($request->input('reset_code'));
        $password = $request->input('password');
        $confirmPassword = $request->input('password_confirmation');

        if (!$email || !$resetCode) {
            return back()->withErrors(['reset_code' => 'Reset code is required']);
        }

        if (!$password || strlen($password) < 6) {
            return back()->withErrors(['password' => 'Password must be at least 6 characters']);
        }

        if ($password !== $confirmPassword) {
            return back()->withErrors(['password_confirmation' => 'Passwords do not match']);
        }

        try {
            // Get user from database
            $user = $this->supabase->query('auth_users', '*', ['email' => $email]);
            
            if (empty($user) || !is_array($user) || count($user) === 0) {
                return back()->withErrors(['reset_code' => 'User not found']);
            }

            $userData = $user[0];

            // Check reset code and expiry
            if ($userData['reset_code'] !== $resetCode) {
                return back()->withErrors(['reset_code' => 'Invalid reset code']);
            }

            if (strtotime($userData['reset_code_expires']) < time()) {
                return back()->withErrors(['reset_code' => 'Reset code has expired. Please request a new one.']);
            }

            // Update password
            $hashedPassword = Hash::make($password);
            $updateData = [
                'password' => $hashedPassword,
                'reset_code' => null,
                'reset_code_expires' => null,
                'updated_at' => now()->toISOString()
            ];

            $result = $this->supabase->update('auth_users', $updateData, ['email' => $email]);

            if (!$result) {
                return back()->withErrors(['reset_code' => 'Password reset failed. Please try again.']);
            }

            return redirect()->route('login')->with('success', 'Password reset successful! Please login with your new password.');

        } catch (\Exception $e) {
            \Log::error('Password reset error: ' . $e->getMessage());
            return back()->withErrors(['reset_code' => 'Password reset failed. Please try again.']);
        }
    }

    // LOGOUT

    public function logout()
    {
        Session::flush();
        return redirect()->route('login')->with('success', 'Logged out successfully');
    }

    // EMAIL HELPER METHODS

    private function sendVerificationEmail($email, $name, $code)
    {
        try {
            Mail::send([], [], function ($message) use ($email, $name, $code) {
                $message->to($email)
                        ->subject('Verify Your Email - Connectly CRM')
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                                <h2 style='color: #4f46e5; text-align: center;'>Verify Your Email</h2>
                                <div style='background: #f8fafc; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0;'>
                                    <p style='font-size: 18px; color: #1f2937; margin-bottom: 10px;'>Hi {$name},</p>
                                    <p style='color: #64748b; margin-bottom: 20px;'>Please enter this verification code to complete your registration:</p>
                                    <p style='font-size: 32px; font-weight: bold; color: #4f46e5; letter-spacing: 4px; margin: 10px 0;'>{$code}</p>
                                </div>
                                <p style='color: #64748b; text-align: center;'>This code expires in 1 hour.</p>
                                <p style='color: #64748b; text-align: center; font-size: 14px;'>If you didn't create an account, please ignore this email.</p>
                            </div>
                        ");
            });
        } catch (\Exception $e) {
            \Log::error('Verification email failed: ' . $e->getMessage());
        }
    }

    private function sendPasswordResetEmail($email, $name, $code)
    {
        try {
            Mail::send([], [], function ($message) use ($email, $name, $code) {
                $message->to($email)
                        ->subject('Password Reset Code - Connectly CRM')
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                                <h2 style='color: #4f46e5; text-align: center;'>Reset Your Password</h2>
                                <div style='background: #f8fafc; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0;'>
                                    <p style='font-size: 18px; color: #1f2937; margin-bottom: 10px;'>Hi {$name},</p>
                                    <p style='color: #64748b; margin-bottom: 20px;'>Use this code to reset your password:</p>
                                    <p style='font-size: 32px; font-weight: bold; color: #4f46e5; letter-spacing: 4px; margin: 10px 0;'>{$code}</p>
                                </div>
                                <p style='color: #64748b; text-align: center;'>This code expires in 30 minutes.</p>
                                <p style='color: #64748b; text-align: center; font-size: 14px;'>If you didn't request a password reset, please ignore this email.</p>
                            </div>
                        ");
            });
        } catch (\Exception $e) {
            \Log::error('Password reset email failed: ' . $e->getMessage());
        }
    }
}