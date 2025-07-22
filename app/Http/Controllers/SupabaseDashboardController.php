<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SupabaseDashboardController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Display the professional dashboard with analytics
     */
    public function index()
    {
        try {
            // Get user from session
            $userEmail = session('user_data.email') ?? session('user_email');
            
            if (!$userEmail) {
                return redirect()->route('login')->with('error', 'Please login to access dashboard.');
            }

            // Get user data
            $users = $this->supabase->query('auth_users', '*', ['email' => $userEmail]);
            
            if (empty($users) || !is_array($users) || count($users) === 0) {
                return redirect()->route('login')->with('error', 'User not found.');
            }

            $user = $users[0];
            $userId = $user['id'];

            // Get all data for this user
            $customers = $this->supabase->query('customers', '*', ['user_id' => $userId]) ?: [];
            $proposals = $this->supabase->query('proposals', '*', ['user_id' => $userId]) ?: [];
            $invoices = $this->supabase->query('invoices', '*', ['user_id' => $userId]) ?: [];
            $transactions = $this->supabase->query('transactions', '*', ['user_id' => $userId]) ?: [];

            // Calculate key metrics
            $metrics = $this->calculateMetrics($customers, $proposals, $invoices, $transactions);
            
            // Generate chart data
            $chartData = $this->generateChartData($customers, $proposals, $invoices, $transactions);
            
            // Get recent activities
            $recentData = $this->getRecentData($customers, $proposals, $invoices, $transactions);

            return view('dashboard-professional', array_merge($metrics, $chartData, $recentData, [
                'user' => $user,
                'user_name' => $user['name'] ?? 'User',
                'user_email' => $user['email']
            ]));

        } catch (\Exception $e) {
            \Log::error('Dashboard error: ' . $e->getMessage());
            
            // Fallback to simple dashboard
            return view('dashboard-simple', [
                'message' => 'Dashboard temporarily simplified due to: ' . $e->getMessage(),
                'user' => session('user_data', ['name' => 'User', 'email' => session('user_email')])
            ]);
        }
    }

    /**
     * Calculate key business metrics
     */
    private function calculateMetrics($customers, $proposals, $invoices, $transactions)
    {
        // Customer metrics
        $totalCustomers = count($customers);
        $activeCustomers = count(array_filter($customers, fn($c) => ($c['status'] ?? 'active') === 'active'));
        $inactiveCustomers = $totalCustomers - $activeCustomers;
        
        $thisMonth = Carbon::now()->startOfMonth();
        $newCustomersThisMonth = count(array_filter($customers, function($c) use ($thisMonth) {
            $createdAt = Carbon::parse($c['created_at'] ?? now());
            return $createdAt->gte($thisMonth);
        }));

        // Proposal metrics
        $totalProposals = count($proposals);
        $pendingProposals = count(array_filter($proposals, fn($p) => ($p['status'] ?? 'draft') === 'sent'));
        $acceptedProposals = count(array_filter($proposals, fn($p) => ($p['status'] ?? 'draft') === 'accepted'));
        $draftProposals = count(array_filter($proposals, fn($p) => ($p['status'] ?? 'draft') === 'draft'));

        // Invoice metrics
        $totalInvoices = count($invoices);
        $pendingInvoices = count(array_filter($invoices, fn($i) => ($i['status'] ?? 'draft') === 'sent'));
        $paidInvoices = count(array_filter($invoices, fn($i) => ($i['status'] ?? 'draft') === 'paid'));
        $overdueInvoices = count(array_filter($invoices, function($i) {
            return ($i['status'] ?? 'draft') === 'sent' && 
                   isset($i['due_date']) && 
                   Carbon::parse($i['due_date'])->lt(Carbon::now());
        }));
        $draftInvoices = count(array_filter($invoices, fn($i) => ($i['status'] ?? 'draft') === 'draft'));

        // Revenue calculations
        $completedTransactions = array_filter($transactions, fn($t) => ($t['status'] ?? 'pending') === 'completed');
        $totalRevenue = array_sum(array_column($completedTransactions, 'amount'));
        
        $monthlyTransactions = array_filter($completedTransactions, function($t) use ($thisMonth) {
            $createdAt = Carbon::parse($t['created_at'] ?? now());
            return $createdAt->gte($thisMonth);
        });
        $monthlyRevenue = array_sum(array_column($monthlyTransactions, 'amount'));

        // Calculate conversion rates
        $proposalAcceptanceRate = $totalProposals > 0 ? round(($acceptedProposals / $totalProposals) * 100, 1) : 0;
        $invoicePaymentRate = $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 1) : 0;

        return compact(
            'totalCustomers', 'activeCustomers', 'inactiveCustomers', 'newCustomersThisMonth',
            'totalProposals', 'pendingProposals', 'acceptedProposals', 'draftProposals',
            'totalInvoices', 'pendingInvoices', 'paidInvoices', 'overdueInvoices', 'draftInvoices',
            'totalRevenue', 'monthlyRevenue',
            'proposalAcceptanceRate', 'invoicePaymentRate'
        );
    }

    /**
     * Generate chart data for analytics
     */
    private function generateChartData($customers, $proposals, $invoices, $transactions)
    {
        // Generate monthly revenue data for the last 12 months
        $revenueByMonth = [];
        $customerGrowth = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthKey = $month->format('Y-m');
            $monthLabel = $month->format('M Y');
            
            // Revenue for this month
            $monthlyTransactions = array_filter($transactions, function($t) use ($month) {
                if (($t['status'] ?? 'pending') !== 'completed') return false;
                $createdAt = Carbon::parse($t['created_at'] ?? now());
                return $createdAt->format('Y-m') === $month->format('Y-m');
            });
            $monthRevenue = array_sum(array_column($monthlyTransactions, 'amount'));
            
            // Customer growth for this month
            $monthlyCustomers = array_filter($customers, function($c) use ($month) {
                $createdAt = Carbon::parse($c['created_at'] ?? now());
                return $createdAt->lte($month->endOfMonth());
            });
            $customerCount = count($monthlyCustomers);
            
            $revenueByMonth[] = [
                'month' => $monthLabel,
                'revenue' => $monthRevenue
            ];
            
            $customerGrowth[] = [
                'month' => $monthLabel,
                'customers' => $customerCount
            ];
        }

        return compact('revenueByMonth', 'customerGrowth');
    }

    /**
     * Get recent data for dashboard widgets
     */
    private function getRecentData($customers, $proposals, $invoices, $transactions)
    {
        // Sort and get recent items
        usort($customers, fn($a, $b) => strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01'));
        usort($transactions, fn($a, $b) => strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01'));
        usort($invoices, fn($a, $b) => strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01'));

        $recentCustomers = array_slice($customers, 0, 5);
        $recentTransactions = array_slice($transactions, 0, 5);
        
        // Get overdue invoices
        $overdueInvoicesList = array_filter($invoices, function($i) {
            return ($i['status'] ?? 'draft') === 'sent' && 
                   isset($i['due_date']) && 
                   Carbon::parse($i['due_date'])->lt(Carbon::now());
        });
        usort($overdueInvoicesList, fn($a, $b) => strtotime($a['due_date'] ?? '1970-01-01') - strtotime($b['due_date'] ?? '1970-01-01'));
        $overdueInvoicesList = array_slice($overdueInvoicesList, 0, 5);

        // Get top customers by revenue
        $customerRevenue = [];
        foreach ($customers as $customer) {
            $customerTransactions = array_filter($transactions, fn($t) => 
                ($t['customer_id'] ?? 0) == ($customer['id'] ?? 0) && 
                ($t['status'] ?? 'pending') === 'completed'
            );
            $revenue = array_sum(array_column($customerTransactions, 'amount'));
            $customerRevenue[] = array_merge($customer, ['total_revenue' => $revenue]);
        }
        usort($customerRevenue, fn($a, $b) => ($b['total_revenue'] ?? 0) - ($a['total_revenue'] ?? 0));
        $topCustomers = array_slice($customerRevenue, 0, 5);

        return compact('recentCustomers', 'recentTransactions', 'overdueInvoicesList', 'topCustomers');
    }
} 