<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserSettingsController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Display user settings page
     */
    public function index()
    {
        try {
            $userEmail = session('user_data.email');
            if (!$userEmail) {
                return redirect()->route('auth.login')->with('error', 'Please login to access settings.');
            }

            // Get current user data
            $users = $this->supabase->query('auth_users', '*', ['email' => $userEmail]);
            
            if (empty($users) || !is_array($users) || count($users) === 0) {
                return redirect()->route('auth.login')->with('error', 'User not found.');
            }

            $user = $users[0];

            // Get user preferences (create default if not exists)
            $preferences = $this->getUserPreferences($user['id']);

            return view('settings.index', [
                'user' => $user,
                'preferences' => $preferences
            ]);

        } catch (\Exception $e) {
            \Log::error('User settings error: ' . $e->getMessage());
            return redirect()->route('dashboard')
                ->with('error', 'Failed to load settings: ' . $e->getMessage());
        }
    }

    /**
     * Update user profile information
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'timezone' => 'required|string|max:50',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $userEmail = session('user_data.email');
            $users = $this->supabase->query('auth_users', '*', ['email' => $userEmail]);
            
            if (empty($users) || !is_array($users)) {
                return back()->with('error', 'User not found.');
            }

            $user = $users[0];
            $updateData = [
                'name' => $validated['name'],
                'company' => $validated['company'],
                'phone' => $validated['phone'],
                'timezone' => $validated['timezone'],
                'updated_at' => now()->toISOString()
            ];

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                $imageName = 'profile_' . $user['id'] . '_' . time() . '.' . $image->getClientOriginalExtension();
                
                // Store image locally (in production, you'd use cloud storage)
                $image->storeAs('public/profiles', $imageName);
                $updateData['profile_image'] = $imageName;
            }

            // Update user in Supabase
            $result = $this->supabase->update('auth_users', $user['id'], $updateData);

            if (!$result) {
                return back()->with('error', 'Failed to update profile.');
            }

            // Update session data
            session(['user_data.name' => $validated['name']]);

            return back()->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Profile update error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update profile: ' . $e->getMessage());
        }
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        try {
            $userEmail = session('user_data.email');
            $users = $this->supabase->query('auth_users', '*', ['email' => $userEmail]);
            
            if (empty($users) || !is_array($users)) {
                return back()->with('error', 'User not found.');
            }

            $user = $users[0];

            // Verify current password
            if (!Hash::check($validated['current_password'], $user['password'])) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            // Update password
            $result = $this->supabase->update('auth_users', $user['id'], [
                'password' => Hash::make($validated['password']),
                'updated_at' => now()->toISOString()
            ]);

            if (!$result) {
                return back()->with('error', 'Failed to update password.');
            }

            return back()->with('success', 'Password updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Password update error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update password: ' . $e->getMessage());
        }
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'email_notifications' => 'boolean',
            'customer_notifications' => 'boolean',
            'proposal_notifications' => 'boolean',
            'invoice_notifications' => 'boolean',
            'payment_notifications' => 'boolean',
            'marketing_emails' => 'boolean',
            'weekly_reports' => 'boolean',
            'language' => 'required|string|in:en,es,fr,de,it',
            'currency' => 'required|string|in:USD,EUR,GBP,CAD,AUD',
            'date_format' => 'required|string|in:MM/DD/YYYY,DD/MM/YYYY,YYYY-MM-DD',
            'theme' => 'required|string|in:light,dark,auto'
        ]);

        try {
            $userEmail = session('user_data.email');
            $users = $this->supabase->query('auth_users', '*', ['email' => $userEmail]);
            
            if (empty($users) || !is_array($users)) {
                return back()->with('error', 'User not found.');
            }

            $user = $users[0];

            // Check if preferences exist
            $existingPrefs = $this->supabase->query('user_preferences', '*', ['user_id' => $user['id']]);

            $preferencesData = [
                'user_id' => $user['id'],
                'email_notifications' => $validated['email_notifications'] ?? false,
                'customer_notifications' => $validated['customer_notifications'] ?? false,
                'proposal_notifications' => $validated['proposal_notifications'] ?? false,
                'invoice_notifications' => $validated['invoice_notifications'] ?? false,
                'payment_notifications' => $validated['payment_notifications'] ?? false,
                'marketing_emails' => $validated['marketing_emails'] ?? false,
                'weekly_reports' => $validated['weekly_reports'] ?? false,
                'language' => $validated['language'],
                'currency' => $validated['currency'],
                'date_format' => $validated['date_format'],
                'theme' => $validated['theme'],
                'updated_at' => now()->toISOString()
            ];

            if (!empty($existingPrefs) && is_array($existingPrefs) && count($existingPrefs) > 0) {
                // Update existing preferences
                $result = $this->supabase->update('user_preferences', $existingPrefs[0]['id'], $preferencesData);
            } else {
                // Create new preferences
                $preferencesData['created_at'] = now()->toISOString();
                $result = $this->supabase->insert('user_preferences', $preferencesData);
            }

            if (!$result) {
                return back()->with('error', 'Failed to update preferences.');
            }

            return back()->with('success', 'Preferences updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Preferences update error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update preferences: ' . $e->getMessage());
        }
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required',
            'confirmation' => 'required|in:DELETE'
        ]);

        try {
            $userEmail = session('user_data.email');
            $users = $this->supabase->query('auth_users', '*', ['email' => $userEmail]);
            
            if (empty($users) || !is_array($users)) {
                return back()->with('error', 'User not found.');
            }

            $user = $users[0];

            // Verify password
            if (!Hash::check($validated['password'], $user['password'])) {
                return back()->withErrors(['password' => 'Password is incorrect.']);
            }

            // Soft delete user (mark as deleted)
            $result = $this->supabase->update('auth_users', $user['id'], [
                'deleted_at' => now()->toISOString(),
                'email' => $user['email'] . '_deleted_' . time(), // Prevent email conflicts
                'updated_at' => now()->toISOString()
            ]);

            if (!$result) {
                return back()->with('error', 'Failed to delete account.');
            }

            // Clear session and redirect
            session()->flush();
            return redirect()->route('auth.login')
                ->with('success', 'Your account has been successfully deleted.');

        } catch (\Exception $e) {
            \Log::error('Account deletion error: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete account: ' . $e->getMessage());
        }
    }

    /**
     * Export user data
     */
    public function exportData()
    {
        try {
            $userEmail = session('user_data.email');
            $users = $this->supabase->query('auth_users', '*', ['email' => $userEmail]);
            
            if (empty($users) || !is_array($users)) {
                return back()->with('error', 'User not found.');
            }

            $user = $users[0];

            // Collect user data
            $userData = [
                'profile' => $user,
                'preferences' => $this->getUserPreferences($user['id']),
                'customers' => $this->supabase->query('customers', '*') ?: [],
                'proposals' => $this->supabase->query('proposals', '*') ?: [],
                'invoices' => $this->supabase->query('invoices', '*') ?: [],
                'transactions' => $this->supabase->query('transactions', '*') ?: [],
                'export_date' => now()->toISOString()
            ];

            $filename = 'user_data_export_' . date('Y-m-d_H-i-s') . '.json';
            
            return response()->json($userData)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            \Log::error('Data export error: ' . $e->getMessage());
            return back()->with('error', 'Failed to export data: ' . $e->getMessage());
        }
    }

    /**
     * Get user preferences with defaults
     */
    private function getUserPreferences($userId)
    {
        $preferences = $this->supabase->query('user_preferences', '*', ['user_id' => $userId]);
        
        if (!empty($preferences) && is_array($preferences) && count($preferences) > 0) {
            return $preferences[0];
        }

        // Return default preferences
        return [
            'email_notifications' => true,
            'customer_notifications' => true,
            'proposal_notifications' => true,
            'invoice_notifications' => true,
            'payment_notifications' => true,
            'marketing_emails' => false,
            'weekly_reports' => true,
            'language' => 'en',
            'currency' => 'USD',
            'date_format' => 'MM/DD/YYYY',
            'theme' => 'light'
        ];
    }
} 