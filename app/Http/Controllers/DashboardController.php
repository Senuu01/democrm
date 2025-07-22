<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Proposal;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\Activity;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Get customer statistics
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $inactiveCustomers = Customer::where('status', 'inactive')->count();
        $newCustomersThisMonth = Customer::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        // Get proposal statistics
        $totalProposals = Proposal::count();
        $proposalsByStatus = Proposal::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        $pendingProposals = Proposal::where('status', 'sent')->count();
        $acceptedProposals = Proposal::where('status', 'accepted')->count();
        
        // Get invoice statistics
        $totalInvoices = Invoice::count();
        $pendingInvoices = Invoice::where('status', 'sent')->count();
        $overdueInvoices = Invoice::where('status', 'overdue')->count();
        $paidInvoices = Invoice::where('status', 'paid')->count();
        $draftInvoices = Invoice::where('status', 'draft')->count();
        
        // Revenue calculations
        $totalRevenue = Transaction::where('status', 'completed')->sum('amount');
        $monthlyRevenue = Transaction::where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
        $quarterlyRevenue = Transaction::where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->whereBetween('created_at', [now()->startOfQuarter(), now()->endOfQuarter()])
            ->sum('amount');
        $yearlyRevenue = Transaction::where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        
        // Revenue trends (last 12 months)
        $revenueByMonth = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $revenue = Transaction::where('status', 'completed')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
            $revenueByMonth[] = [
                'month' => $month->format('M Y'),
                'revenue' => $revenue
            ];
        }
        
        // Customer growth trend (last 6 months)
        $customerGrowth = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = Customer::whereYear('created_at', '<=', $month->year)
                ->where(function($query) use ($month) {
                    $query->whereYear('created_at', '<', $month->year)
                        ->orWhere(function($subQuery) use ($month) {
                            $subQuery->whereYear('created_at', $month->year)
                                ->whereMonth('created_at', '<=', $month->month);
                        });
                })
                ->count();
            $customerGrowth[] = [
                'month' => $month->format('M Y'),
                'customers' => $count
            ];
        }
        
        // Top customers by revenue
        $topCustomers = Customer::select('customers.*')
            ->selectRaw('COALESCE(SUM(transactions.amount), 0) as total_revenue')
            ->leftJoin('transactions', function($join) {
                $join->on('customers.id', '=', 'transactions.customer_id')
                     ->where('transactions.status', '=', 'completed');
            })
            ->groupBy('customers.id')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get();
        
        // Recent activities
        $recentActivities = Activity::with(['user'])
            ->latest()
            ->take(10)
            ->get();
        
        // Recent customers
        $recentCustomers = Customer::with(['proposals', 'invoices'])
            ->latest()
            ->take(5)
            ->get();
        
        // Recent transactions
        $recentTransactions = Transaction::with(['customer', 'invoice'])
            ->latest()
            ->take(5)
            ->get();
        
        // Overdue invoices
        $overdueInvoicesList = Invoice::with('customer')
            ->where('status', 'sent')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->take(5)
            ->get();
        
        // Conversion rates
        $proposalAcceptanceRate = $totalProposals > 0 
            ? round(($acceptedProposals / $totalProposals) * 100, 1) 
            : 0;
        $invoicePaymentRate = $totalInvoices > 0 
            ? round(($paidInvoices / $totalInvoices) * 100, 1) 
            : 0;
        
        return view('dashboard', compact(
            'totalCustomers', 'activeCustomers', 'inactiveCustomers', 'newCustomersThisMonth',
            'totalProposals', 'proposalsByStatus', 'pendingProposals', 'acceptedProposals',
            'totalInvoices', 'pendingInvoices', 'overdueInvoices', 'paidInvoices', 'draftInvoices',
            'totalRevenue', 'monthlyRevenue', 'quarterlyRevenue', 'yearlyRevenue',
            'revenueByMonth', 'customerGrowth', 'topCustomers',
            'recentActivities', 'recentCustomers', 'recentTransactions', 'overdueInvoicesList',
            'proposalAcceptanceRate', 'invoicePaymentRate'
        ));
    }
} 