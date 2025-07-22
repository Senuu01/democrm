<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Display analytics dashboard
     */
    public function index(Request $request)
    {
        try {
            $timeframe = $request->get('timeframe', '30'); // Default to 30 days
            $startDate = Carbon::now()->subDays($timeframe)->startOfDay();
            
            // Get all data
            $customers = $this->supabase->query('customers', '*') ?: [];
            $proposals = $this->supabase->query('proposals', '*') ?: [];
            $invoices = $this->supabase->query('invoices', '*') ?: [];
            $transactions = $this->supabase->query('transactions', '*') ?: [];

            // Calculate key metrics
            $metrics = $this->calculateKeyMetrics($customers, $proposals, $invoices, $transactions, $startDate);
            
            // Generate chart data
            $chartData = $this->generateChartData($customers, $proposals, $invoices, $transactions, $timeframe);
            
            // Get recent activities
            $recentActivities = $this->getRecentActivities($customers, $proposals, $invoices, $transactions);
            
            // Get top customers
            $topCustomers = $this->getTopCustomers($customers, $invoices, $transactions);
            
            // Calculate conversion rates
            $conversionRates = $this->calculateConversionRates($proposals, $invoices);

            return view('analytics.index', [
                'metrics' => $metrics,
                'chartData' => $chartData,
                'recentActivities' => $recentActivities,
                'topCustomers' => $topCustomers,
                'conversionRates' => $conversionRates,
                'timeframe' => $timeframe
            ]);

        } catch (\Exception $e) {
            \Log::error('Analytics error: ' . $e->getMessage());
            return view('analytics.index', [
                'metrics' => $this->getEmptyMetrics(),
                'chartData' => $this->getEmptyChartData(),
                'recentActivities' => [],
                'topCustomers' => [],
                'conversionRates' => [],
                'timeframe' => $timeframe,
                'error' => 'Failed to load analytics: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate key business metrics
     */
    private function calculateKeyMetrics($customers, $proposals, $invoices, $transactions, $startDate)
    {
        $now = Carbon::now();
        
        // Filter data by timeframe
        $recentCustomers = array_filter($customers, function($customer) use ($startDate) {
            return Carbon::parse($customer['created_at'] ?? 'now')->gte($startDate);
        });
        
        $recentProposals = array_filter($proposals, function($proposal) use ($startDate) {
            return Carbon::parse($proposal['created_at'] ?? 'now')->gte($startDate);
        });
        
        $recentInvoices = array_filter($invoices, function($invoice) use ($startDate) {
            return Carbon::parse($invoice['created_at'] ?? 'now')->gte($startDate);
        });
        
        $recentTransactions = array_filter($transactions, function($transaction) use ($startDate) {
            return Carbon::parse($transaction['created_at'] ?? 'now')->gte($startDate);
        });

        // Calculate metrics
        $totalRevenue = array_sum(array_column(array_filter($transactions, fn($t) => ($t['status'] ?? '') === 'completed'), 'amount'));
        $recentRevenue = array_sum(array_column(array_filter($recentTransactions, fn($t) => ($t['status'] ?? '') === 'completed'), 'amount'));
        
        $totalCustomers = count($customers);
        $newCustomers = count($recentCustomers);
        
        $totalProposals = count($proposals);
        $newProposals = count($recentProposals);
        
        $totalInvoices = count($invoices);
        $newInvoices = count($recentInvoices);
        
        $paidInvoices = count(array_filter($invoices, fn($i) => ($i['status'] ?? '') === 'paid'));
        $pendingInvoices = count(array_filter($invoices, fn($i) => in_array(($i['status'] ?? ''), ['sent', 'draft'])));
        
        $overdueInvoices = count(array_filter($invoices, function($invoice) use ($now) {
            return ($invoice['status'] ?? '') === 'sent' && 
                   isset($invoice['due_date']) && 
                   Carbon::parse($invoice['due_date'])->lt($now);
        }));
        
        $outstandingAmount = array_sum(array_column(array_filter($invoices, fn($i) => ($i['status'] ?? '') === 'sent'), 'total_amount'));
        
        // Calculate growth rates (mock calculation for demo)
        $customerGrowthRate = $totalCustomers > 0 ? ($newCustomers / max($totalCustomers - $newCustomers, 1)) * 100 : 0;
        $revenueGrowthRate = $totalRevenue > 0 ? (($recentRevenue / max($totalRevenue - $recentRevenue, 1)) * 100) : 0;
        
        return [
            'total_revenue' => $totalRevenue,
            'recent_revenue' => $recentRevenue,
            'revenue_growth' => $revenueGrowthRate,
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomers,
            'customer_growth' => $customerGrowthRate,
            'total_proposals' => $totalProposals,
            'new_proposals' => $newProposals,
            'total_invoices' => $totalInvoices,
            'new_invoices' => $newInvoices,
            'paid_invoices' => $paidInvoices,
            'pending_invoices' => $pendingInvoices,
            'overdue_invoices' => $overdueInvoices,
            'outstanding_amount' => $outstandingAmount,
            'average_invoice_value' => $totalInvoices > 0 ? $totalRevenue / $totalInvoices : 0,
            'conversion_rate' => $totalProposals > 0 ? ($paidInvoices / $totalProposals) * 100 : 0
        ];
    }

    /**
     * Generate chart data for various visualizations
     */
    private function generateChartData($customers, $proposals, $invoices, $transactions, $timeframe)
    {
        $days = (int) $timeframe;
        $dates = [];
        $revenueData = [];
        $customerData = [];
        $proposalData = [];
        
        // Generate date range
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dates[] = Carbon::now()->subDays($i)->format('M j');
            
            // Revenue by day
            $dayRevenue = 0;
            foreach ($transactions as $transaction) {
                if (($transaction['status'] ?? '') === 'completed' && 
                    Carbon::parse($transaction['created_at'] ?? 'now')->format('Y-m-d') === $date) {
                    $dayRevenue += $transaction['amount'] ?? 0;
                }
            }
            $revenueData[] = $dayRevenue;
            
            // New customers by day
            $dayCustomers = 0;
            foreach ($customers as $customer) {
                if (Carbon::parse($customer['created_at'] ?? 'now')->format('Y-m-d') === $date) {
                    $dayCustomers++;
                }
            }
            $customerData[] = $dayCustomers;
            
            // New proposals by day
            $dayProposals = 0;
            foreach ($proposals as $proposal) {
                if (Carbon::parse($proposal['created_at'] ?? 'now')->format('Y-m-d') === $date) {
                    $dayProposals++;
                }
            }
            $proposalData[] = $dayProposals;
        }

        // Invoice status distribution
        $invoiceStatusData = [
            'draft' => count(array_filter($invoices, fn($i) => ($i['status'] ?? 'draft') === 'draft')),
            'sent' => count(array_filter($invoices, fn($i) => ($i['status'] ?? 'draft') === 'sent')),
            'paid' => count(array_filter($invoices, fn($i) => ($i['status'] ?? 'draft') === 'paid')),
            'overdue' => count(array_filter($invoices, function($i) {
                return ($i['status'] ?? 'draft') === 'sent' && 
                       isset($i['due_date']) && 
                       Carbon::parse($i['due_date'])->lt(Carbon::now());
            })),
            'cancelled' => count(array_filter($invoices, fn($i) => ($i['status'] ?? 'draft') === 'cancelled'))
        ];

        // Proposal status distribution
        $proposalStatusData = [
            'draft' => count(array_filter($proposals, fn($p) => ($p['status'] ?? 'draft') === 'draft')),
            'sent' => count(array_filter($proposals, fn($p) => ($p['status'] ?? 'draft') === 'sent')),
            'accepted' => count(array_filter($proposals, fn($p) => ($p['status'] ?? 'draft') === 'accepted')),
            'rejected' => count(array_filter($proposals, fn($p) => ($p['status'] ?? 'draft') === 'rejected'))
        ];

        return [
            'dates' => $dates,
            'revenue' => $revenueData,
            'customers' => $customerData,
            'proposals' => $proposalData,
            'invoice_status' => $invoiceStatusData,
            'proposal_status' => $proposalStatusData
        ];
    }

    /**
     * Get recent activities across all entities
     */
    private function getRecentActivities($customers, $proposals, $invoices, $transactions)
    {
        $activities = [];

        // Recent customers
        foreach (array_slice($customers, -10) as $customer) {
            $activities[] = [
                'type' => 'customer',
                'icon' => 'fas fa-user-plus',
                'color' => 'text-blue-500',
                'title' => 'New customer added',
                'description' => $customer['name'] ?? 'Unknown',
                'time' => Carbon::parse($customer['created_at'] ?? 'now')->diffForHumans()
            ];
        }

        // Recent proposals
        foreach (array_slice($proposals, -10) as $proposal) {
            $activities[] = [
                'type' => 'proposal',
                'icon' => 'fas fa-file-alt',
                'color' => 'text-purple-500',
                'title' => 'Proposal ' . ($proposal['status'] ?? 'created'),
                'description' => $proposal['title'] ?? 'Untitled',
                'time' => Carbon::parse($proposal['updated_at'] ?? $proposal['created_at'] ?? 'now')->diffForHumans()
            ];
        }

        // Recent invoices
        foreach (array_slice($invoices, -10) as $invoice) {
            $activities[] = [
                'type' => 'invoice',
                'icon' => 'fas fa-file-invoice',
                'color' => 'text-green-500',
                'title' => 'Invoice ' . ($invoice['status'] ?? 'created'),
                'description' => $invoice['title'] ?? 'Untitled',
                'time' => Carbon::parse($invoice['updated_at'] ?? $invoice['created_at'] ?? 'now')->diffForHumans()
            ];
        }

        // Recent transactions
        foreach (array_slice($transactions, -10) as $transaction) {
            $activities[] = [
                'type' => 'transaction',
                'icon' => 'fas fa-credit-card',
                'color' => 'text-emerald-500',
                'title' => 'Payment received',
                'description' => '$' . number_format($transaction['amount'] ?? 0, 2),
                'time' => Carbon::parse($transaction['created_at'] ?? 'now')->diffForHumans()
            ];
        }

        // Sort by time and take most recent 15
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });

        return array_slice($activities, 0, 15);
    }

    /**
     * Get top customers by revenue
     */
    private function getTopCustomers($customers, $invoices, $transactions)
    {
        $customerRevenue = [];

        foreach ($customers as $customer) {
            $customerId = $customer['id'];
            $revenue = 0;
            $invoiceCount = 0;

            // Calculate revenue from transactions
            foreach ($transactions as $transaction) {
                if (($transaction['customer_id'] ?? 0) == $customerId && 
                    ($transaction['status'] ?? '') === 'completed') {
                    $revenue += $transaction['amount'] ?? 0;
                }
            }

            // Count invoices
            foreach ($invoices as $invoice) {
                if (($invoice['customer_id'] ?? 0) == $customerId) {
                    $invoiceCount++;
                }
            }

            if ($revenue > 0 || $invoiceCount > 0) {
                $customerRevenue[] = [
                    'customer' => $customer,
                    'revenue' => $revenue,
                    'invoice_count' => $invoiceCount,
                    'average_invoice' => $invoiceCount > 0 ? $revenue / $invoiceCount : 0
                ];
            }
        }

        // Sort by revenue descending
        usort($customerRevenue, function($a, $b) {
            return $b['revenue'] - $a['revenue'];
        });

        return array_slice($customerRevenue, 0, 10);
    }

    /**
     * Calculate conversion rates
     */
    private function calculateConversionRates($proposals, $invoices)
    {
        $totalProposals = count($proposals);
        $acceptedProposals = count(array_filter($proposals, fn($p) => ($p['status'] ?? '') === 'accepted'));
        $rejectedProposals = count(array_filter($proposals, fn($p) => ($p['status'] ?? '') === 'rejected'));
        
        $totalInvoices = count($invoices);
        $paidInvoices = count(array_filter($invoices, fn($i) => ($i['status'] ?? '') === 'paid'));
        
        return [
            'proposal_to_acceptance' => $totalProposals > 0 ? ($acceptedProposals / $totalProposals) * 100 : 0,
            'proposal_to_rejection' => $totalProposals > 0 ? ($rejectedProposals / $totalProposals) * 100 : 0,
            'invoice_to_payment' => $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0,
            'overall_conversion' => $totalProposals > 0 ? ($paidInvoices / $totalProposals) * 100 : 0
        ];
    }

    /**
     * Get empty metrics for error states
     */
    private function getEmptyMetrics()
    {
        return [
            'total_revenue' => 0,
            'recent_revenue' => 0,
            'revenue_growth' => 0,
            'total_customers' => 0,
            'new_customers' => 0,
            'customer_growth' => 0,
            'total_proposals' => 0,
            'new_proposals' => 0,
            'total_invoices' => 0,
            'new_invoices' => 0,
            'paid_invoices' => 0,
            'pending_invoices' => 0,
            'overdue_invoices' => 0,
            'outstanding_amount' => 0,
            'average_invoice_value' => 0,
            'conversion_rate' => 0
        ];
    }

    /**
     * Get empty chart data for error states
     */
    private function getEmptyChartData()
    {
        return [
            'dates' => [],
            'revenue' => [],
            'customers' => [],
            'proposals' => [],
            'invoice_status' => ['draft' => 0, 'sent' => 0, 'paid' => 0, 'overdue' => 0, 'cancelled' => 0],
            'proposal_status' => ['draft' => 0, 'sent' => 0, 'accepted' => 0, 'rejected' => 0]
        ];
    }
} 