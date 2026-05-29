<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Package;
use App\Models\PaymentRequest;
use App\Models\PaymentTransaction;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AdminSubscriptionController extends Controller
{
    /**
     * Constructor with admin auth middleware
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    // ============================================
    // DASHBOARD
    // ============================================

    /**
     * Subscription dashboard with overview metrics
     */
    public function dashboard(Request $request)
    {
        $period = $request->get('period', '30_days');
        $startDate = $request->get('start_date');
        $endDate   = $request->get('end_date');
        $dateRange = $this->getDateRange($period, $startDate, $endDate);

        $data = [
            'overview' => $this->getOverviewMetrics($dateRange),
            'recent_requests' => $this->getRecentPaymentRequests(),
            'recent_transactions' => $this->getRecentTransactions(),
            'package_stats' => $this->getPackageStats($dateRange),
            'monthly_revenue' => $this->getMonthlyRevenue(),
            'top_packages' => $this->getTopPackages($dateRange),
            'period' => $period,
            'date_range' => $dateRange,
        ];

        return view('admin.subscription.dashboard', $data);
    }

    /**
     * Get date range based on period
     */
    private function getDateRange($period, $startDate = null, $endDate = null)
    {
        $end = Carbon::now();
        
        switch ($period) {
            case 'today':
                $start = Carbon::today();
                break;
            case 'yesterday':
                $start = Carbon::yesterday();
                $end = Carbon::yesterday()->endOfDay();
                break;
            case '7_days':
                $start = Carbon::now()->subDays(7);
                break;
            case '30_days':
                $start = Carbon::now()->subDays(30);
                break;
            case '90_days':
                $start = Carbon::now()->subDays(90);
                break;
            case 'this_month':
                $start = Carbon::now()->startOfMonth();
                break;
            case 'last_month':
                $start = Carbon::now()->subMonth()->startOfMonth();
                $end = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $start = Carbon::now()->startOfYear();
                break;
            case 'custom':
                $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subDays(30);
                $end   = $endDate   ? Carbon::parse($endDate)->endOfDay()     : Carbon::now()->endOfDay();
                break;
            default:
                $start = Carbon::now()->subDays(30);
        }

        return [
            'start' => $start,
            'end' => $end,
            'start_formatted' => $start->format('Y-m-d'),
            'end_formatted' => $end->format('Y-m-d'),
            'period' => $period,
        ];
    }

    /**
     * Get overview metrics
     */
    private function getOverviewMetrics($dateRange)
    {
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        $totalPackages = Package::count();
        $activePackages = Package::where('status', true)->count();
        
        $totalRequests = PaymentRequest::whereBetween('created_at', [$start, $end])->count();
        $pendingRequests = PaymentRequest::where('status', 'pending')
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $paidRequests = PaymentRequest::where('status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->count();
        
        $totalTransactions = PaymentTransaction::where('status', 'success')
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $totalRevenue = PaymentTransaction::where('status', 'success')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
        
        $companiesWithPackage = User::where('usertype', 'company')
            ->whereNotNull('package_id')
            ->count();
        
        $totalCompanies = User::where('usertype', 'company')->count();
        $packageAdoptionRate = $totalCompanies > 0 ? round(($companiesWithPackage / $totalCompanies) * 100, 2) : 0;

        return [
            'packages' => [
                'total' => $totalPackages,
                'active' => $activePackages,
            ],
            'requests' => [
                'total' => $totalRequests,
                'pending' => $pendingRequests,
                'paid' => $paidRequests,
            ],
            'transactions' => [
                'total' => $totalTransactions,
                'total_revenue' => $totalRevenue,
                'formatted_total_revenue' => '₹ ' . number_format($totalRevenue, 2),
            ],
            'companies' => [
                'total' => $totalCompanies,
                'with_package' => $companiesWithPackage,
                'adoption_rate' => $packageAdoptionRate,
            ],
        ];
    }

    /**
     * Get recent payment requests
     */
    private function getRecentPaymentRequests()
    {
        return PaymentRequest::with(['user', 'package'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($request) {
                $request->user_name = $request->user ? $this->getUserDisplayName($request->user) : 'Unknown';
                return $request;
            });
    }

    /**
     * Get recent transactions
     */
    private function getRecentTransactions()
    {
        return PaymentTransaction::with(['paymentRequest.user', 'paymentRequest.package'])
            ->where('status', 'success')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($transaction) {
                if ($transaction->paymentRequest && $transaction->paymentRequest->user) {
                    $transaction->user_name = $this->getUserDisplayName($transaction->paymentRequest->user);
                } else {
                    $transaction->user_name = 'Unknown';
                }
                $transaction->package_title = $transaction->paymentRequest->package->package_title ?? 'N/A';
                return $transaction;
            });
    }

    /**
     * Get package stats
     */
    private function getPackageStats($dateRange)
    {
        $packages = Package::all();
        $stats = [];
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        foreach ($packages as $package) {
            $purchases = PaymentRequest::where('package_id', $package->id)
                ->where('status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->count();
            
            $revenue = PaymentTransaction::whereIn('payment_request_id', function($q) use ($package, $start, $end) {
                    $q->select('id')->from('payment_requests')->where('package_id', $package->id);
                })
                ->where('status', 'success')
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount');

            $stats[] = [
                'id' => $package->id,
                'title' => $package->package_title,
                'price' => $package->package_price,
                'formatted_price' => $package->formatted_price,
                'purchases' => $purchases,
                'revenue' => $revenue,
                'formatted_revenue' => '₹ ' . number_format($revenue, 2),
            ];
        }

        return collect($stats)->sortByDesc('purchases')->values();
    }

    /**
     * Get monthly revenue data
     */
    private function getMonthlyRevenue()
    {
        $data = [];
        $start = Carbon::now()->subMonths(11)->startOfMonth();
        
        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $revenue = PaymentTransaction::where('status', 'success')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount');
            
            $count = PaymentTransaction::where('status', 'success')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $data[] = [
                'month' => $month->format('M Y'),
                'revenue' => $revenue,
                'formatted_revenue' => '₹ ' . number_format($revenue, 2),
                'count' => $count,
            ];
        }

        return $data;
    }

    /**
     * Get top packages by revenue
     */
    private function getTopPackages($dateRange, $limit = 5)
    {
        $packages = Package::all();
        $topPackages = [];
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        foreach ($packages as $package) {
            $revenue = PaymentTransaction::whereIn('payment_request_id', function($q) use ($package) {
                    $q->select('id')->from('payment_requests')->where('package_id', $package->id);
                })
                ->where('status', 'success')
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount');
            
            $count = PaymentRequest::where('package_id', $package->id)
                ->where('status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $topPackages[] = [
                'id' => $package->id,
                'title' => $package->package_title,
                'price' => $package->package_price,
                'formatted_price' => $package->formatted_price,
                'purchases' => $count,
                'revenue' => $revenue,
                'formatted_revenue' => '₹ ' . number_format($revenue, 2),
            ];
        }

        return collect($topPackages)->sortByDesc('revenue')->take($limit)->values();
    }

    /**
     * Get user display name
     */
    private function getUserDisplayName($user)
    {
        if (!$user) {
            return 'Unknown';
        }

        if ($user->usertype === 'company') {
            return $user->company_name ?? $user->name ?? 'Unknown Company';
        }

        return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Unknown User');
    }

    // ============================================
    // PACKAGE MANAGEMENT
    // ============================================

    /**
     * List all packages
     */
    public function packages(Request $request)
    {
        $query = Package::orderBy('package_for')->orderBy('sort_order');

        // Apply filters
        if ($request->filled('package_for')) {
            $query->where('package_for', $request->package_for);
        }

        if ($request->filled('is_active') && $request->is_active != '-1') {
            $query->where('status', $request->is_active);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('package_title', 'LIKE', "%{$search}%")
                  ->orWhere('package_subtitle', 'LIKE', "%{$search}%");
            });
        }

        $packages = $query->paginate(20);

        // Get purchase stats for each package
        foreach ($packages as $package) {
            $package->purchases_count = PaymentRequest::where('package_id', $package->id)
                ->where('status', 'paid')
                ->count();
            
            $package->revenue = PaymentTransaction::whereIn('payment_request_id', function($q) use ($package) {
                    $q->select('id')->from('payment_requests')->where('package_id', $package->id);
                })
                ->where('status', 'success')
                ->sum('amount');
            
            $package->formatted_revenue = '₹ ' . number_format($package->revenue, 2);
        }

        return view('admin.subscription.packages.index', compact('packages'));
    }

    /**
     * Show create package form
     */
    public function createPackage()
    {
        return view('admin.subscription.packages.create');
    }

    /**
     * Store new package
     */
    public function storePackage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_title' => 'required|string|max:255',
            'package_subtitle' => 'nullable|string|max:255',
            'package_price' => 'required|numeric|min:0',
            'package_num_days' => 'required|integer|min:1',
            'package_num_listings' => 'required|integer|min:1',
            'package_for' => 'required|in:employer,job_seeker,cv_search,featured',
            'package_features' => 'nullable|string',
            'is_popular' => 'boolean',
            'badge_text' => 'nullable|string|max:50',
            'sort_order' => 'integer',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();
        $data['status'] = $request->has('status');
        $data['is_popular'] = $request->has('is_popular');

        Package::create($data);

        return redirect()->route('admin.subscriptions.packages')
            ->with('success', 'Package created successfully');
    }

    /**
     * Show edit package form
     */
    public function editPackage($id)
    {
        $package = Package::findOrFail($id);
        return view('admin.subscription.packages.edit', compact('package'));
    }

    /**
     * Update package
     */
    public function updatePackage(Request $request, $id)
    {
        $package = Package::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'package_title' => 'required|string|max:255',
            'package_subtitle' => 'nullable|string|max:255',
            'package_price' => 'required|numeric|min:0',
            'package_num_days' => 'required|integer|min:1',
            'package_num_listings' => 'required|integer|min:1',
            'package_for' => 'required|in:employer,job_seeker,cv_search,featured',
            'package_features' => 'nullable|string',
            'is_popular' => 'boolean',
            'badge_text' => 'nullable|string|max:50',
            'sort_order' => 'integer',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();
        $data['status'] = $request->has('status');
        $data['is_popular'] = $request->has('is_popular');

        $package->update($data);

        return redirect()->route('admin.subscriptions.packages')
            ->with('success', 'Package updated successfully');
    }

    /**
     * Delete package
     */
    public function deletePackage($id)
    {
        $package = Package::findOrFail($id);
        
        // Check if package has any purchases
        $hasPurchases = PaymentRequest::where('package_id', $id)->exists();
        
        if ($hasPurchases) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete package with existing purchases'
            ], 400);
        }

        $package->delete();

        return response()->json([
            'success' => true,
            'message' => 'Package deleted successfully'
        ]);
    }

    /**
     * Toggle package status
     */
    public function togglePackageStatus($id)
    {
        $package = Package::findOrFail($id);
        $package->status = !$package->status;
        $package->save();

        return response()->json([
            'success' => true,
            'message' => 'Package status updated',
            'new_status' => $package->status
        ]);
    }

    // ============================================
    // PAYMENT REQUESTS
    // ============================================

    /**
     * List all payment requests
     */
    public function paymentRequests(Request $request)
    {
        $query = PaymentRequest::with(['user', 'package']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(20);

        // Add user display names
        foreach ($requests as $req) {
            $req->user_display_name = $req->user ? $this->getUserDisplayName($req->user) : 'N/A';
        }

        return view('admin.subscription.requests.index', compact('requests'));
    }

    /**
     * View payment request details
     */
    public function viewPaymentRequest($id)
    {
        $request = PaymentRequest::with(['user', 'package', 'transaction'])
            ->findOrFail($id);

        $request->user_display_name = $request->user ? $this->getUserDisplayName($request->user) : 'N/A';

        return view('admin.subscription.requests.view', compact('request'));
    }

    /**
     * Update payment request status
     */
    public function updatePaymentRequestStatus(Request $request, $id)
    {
        $paymentRequest = PaymentRequest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,paid,failed,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $oldStatus = $paymentRequest->status;
        $paymentRequest->status = $request->status;
        $paymentRequest->admin_notes = $request->notes;
        $paymentRequest->save();

        // If manually marking as paid, create transaction and activate package
        if ($request->status === 'paid' && $oldStatus !== 'paid') {
            DB::transaction(function() use ($paymentRequest) {
                PaymentTransaction::create([
                    'payment_request_id' => $paymentRequest->id,
                    'razorpay_payment_id' => 'MANUAL_' . Str::random(10),
                    'razorpay_order_id' => 'MANUAL_' . Str::random(10),
                    'payment_method' => 'manual',
                    'amount' => $paymentRequest->amount,
                    'currency' => $paymentRequest->currency,
                    'status' => 'success',
                    'payment_response' => json_encode(['manual_by_admin' => auth()->guard('admin')->user()->id]),
                    'created_at' => now()
                ]);

                // Activate package for user
                $this->activatePackageForUser($paymentRequest);
            });
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment request status updated',
            'new_status' => $paymentRequest->status
        ]);
    }

    /**
     * Activate package for user
     */
    private function activatePackageForUser($paymentRequest)
    {
        $user = $paymentRequest->user;
        
        if ($user && $user->usertype === 'company') {
            $package = $paymentRequest->package;
            
            $user->package_id = $package->id;
            $user->package_start_date = now();
            $user->package_end_date = now()->addDays($package->package_num_days);
            $user->jobs_quota = $package->package_num_listings;
            $user->availed_jobs_quota = 0;
            $user->save();

            Log::info('Package activated for company user', [
                'user_id' => $user->id,
                'company_name' => $user->company_name ?? $user->name,
                'package_id' => $package->id,
                'payment_request_id' => $paymentRequest->id
            ]);
        }
    }

    // ============================================
    // TRANSACTIONS
    // ============================================

    /**
     * List all transactions
     */
    public function transactions(Request $request)
    {
        $query = PaymentTransaction::with(['paymentRequest.user', 'paymentRequest.package']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('razorpay_payment_id', 'LIKE', "%{$search}%")
                  ->orWhere('razorpay_order_id', 'LIKE', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        // Calculate totals
        $totalAmount = $query->sum('amount');
        $successCount = (clone $query)->where('status', 'success')->count();
        $successAmount = (clone $query)->where('status', 'success')->sum('amount');
        $failedCount = (clone $query)->where('status', 'failed')->count();

        // Add user names
        foreach ($transactions as $transaction) {
            if ($transaction->paymentRequest && $transaction->paymentRequest->user) {
                $transaction->user_name = $this->getUserDisplayName($transaction->paymentRequest->user);
            } else {
                $transaction->user_name = 'N/A';
            }
            $transaction->package_title = $transaction->paymentRequest->package->package_title ?? 'N/A';
        }

        return view('admin.subscription.transactions.index', compact('transactions', 'totalAmount', 'successCount', 'successAmount', 'failedCount'));
    }

    /**
     * View transaction details
     */
    public function viewTransaction($id)
    {
        $transaction = PaymentTransaction::with(['paymentRequest.user', 'paymentRequest.package'])
            ->findOrFail($id);

        if ($transaction->paymentRequest && $transaction->paymentRequest->user) {
            $transaction->user_name = $this->getUserDisplayName($transaction->paymentRequest->user);
            $transaction->user = $transaction->paymentRequest->user;
        }
        $transaction->package = $transaction->paymentRequest->package ?? null;

        return view('admin.subscription.transactions.view', compact('transaction'));
    }

    /**
     * Export transactions as CSV
     */
    public function exportTransactions(Request $request)
    {
        $query = PaymentTransaction::with(['paymentRequest.user', 'paymentRequest.package']);

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        $filename = 'transactions_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = ['ID', 'Transaction ID', 'Order ID', 'Name', 'Unique ID', 'Email', 'Phone', 'Package', 'Amount', 'Payment Method', 'Status', 'Date'];

        $callback = function() use ($transactions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($transactions as $transaction) {
                $user = $transaction->paymentRequest->user ?? null;
                $userName = $user ? $this->getUserDisplayName($user) : 'N/A';
                $uniqueID = $user->unique_id ?? 'N/A';
                $email = $user->email ?? 'N/A';
                $phone = $user->phone ?? $user->mobile_num ?? 'N/A';

                $packageTitle = $transaction->paymentRequest->package->package_title ?? 'N/A';

                fputcsv($file, [
                    $transaction->id,
                    $transaction->razorpay_payment_id,
                    $transaction->razorpay_order_id,
                    $userName,
                    $uniqueID,
                    $email,
                    $phone,
                    $packageTitle,
                    $transaction->amount,
                    $transaction->payment_method,
                    $transaction->status,
                    $transaction->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ============================================
    // COMPANY SUBSCRIPTIONS
    // ============================================

    /**
     * List all companies with their subscription info
     */
    public function companySubscriptions(Request $request)
    {
        $query = User::where('usertype', 'company');

        // Apply filters
        if ($request->filled('has_package')) {
            if ($request->has_package == 'yes') {
                $query->whereNotNull('package_id');
            } elseif ($request->has_package == 'no') {
                $query->whereNull('package_id');
            }
        }

        if ($request->filled('package_id')) {
            $query->where('package_id', $request->package_id);
        }

        if ($request->filled('status')) {
            if ($request->status == 'active') {
                $query->whereNotNull('package_end_date')
                      ->where('package_end_date', '>=', now());
            } elseif ($request->status == 'expired') {
                $query->whereNotNull('package_end_date')
                      ->where('package_end_date', '<', now());
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $companies = $query->paginate(20);

        // Load package details and calculate usage
        foreach ($companies as $company) {
            if ($company->package_id) {
                $company->package = Package::find($company->package_id);
                $company->package_usage = [
                    'total' => $company->jobs_quota ?? 0,
                    'used' => $company->availed_jobs_quota ?? 0,
                    'remaining' => ($company->jobs_quota ?? 0) - ($company->availed_jobs_quota ?? 0),
                    'percentage' => ($company->jobs_quota ?? 0) > 0 
                        ? round(($company->availed_jobs_quota ?? 0) / $company->jobs_quota * 100, 2) 
                        : 0,
                ];
                
                $company->is_active = $company->package_end_date && Carbon::parse($company->package_end_date)->isFuture();
                $company->days_remaining = $company->is_active ? now()->diffInDays(Carbon::parse($company->package_end_date), false) : 0;
            } else {
                $company->package = null;
                $company->package_usage = null;
                $company->is_active = false;
                $company->days_remaining = 0;
            }
        }

        // Get all packages for filter dropdown
        $packages = Package::where('status', true)->get();

        return view('admin.subscription.companies.index', compact('companies', 'packages'));
    }

    /**
     * View company subscription details
     */
    public function viewCompanySubscription($id)
    {
        $company = User::where('usertype', 'company')->findOrFail($id);

        if ($company->package_id) {
            $company->package = Package::find($company->package_id);
            $company->package_usage = [
                'total' => $company->jobs_quota ?? 0,
                'used' => $company->availed_jobs_quota ?? 0,
                'remaining' => ($company->jobs_quota ?? 0) - ($company->availed_jobs_quota ?? 0),
                'percentage' => ($company->jobs_quota ?? 0) > 0 
                    ? round(($company->availed_jobs_quota ?? 0) / $company->jobs_quota * 100, 2) 
                    : 0,
            ];
            
            $company->is_active = $company->package_end_date && Carbon::parse($company->package_end_date)->isFuture();
            $company->days_remaining = $company->is_active ? now()->diffInDays(Carbon::parse($company->package_end_date), false) : 0;
        } else {
            $company->package = null;
            $company->package_usage = null;
            $company->is_active = false;
            $company->days_remaining = 0;
        }

        // Get payment history for this company
        $paymentRequests = PaymentRequest::where('user_id', $company->id)
            ->with(['package', 'transaction'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get jobs posted by this company
        $jobs = \App\Job::where('company_id', $company->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get all packages for update dropdown
        $packages = Package::where('status', true)->get();

        return view('admin.subscription.companies.view', compact('company', 'paymentRequests', 'jobs', 'packages'));
    }

    /**
     * Update company package
     */
    public function updateCompanyPackage(Request $request, $id)
    {
        $company = User::where('usertype', 'company')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:packages,id',
            'package_start_date' => 'nullable|date',
            'package_end_date' => 'nullable|date|after:package_start_date',
            'jobs_quota' => 'nullable|integer|min:1',
            'availed_jobs_quota' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $package = Package::find($request->package_id);

        $company->package_id = $package->id;
        $company->package_start_date = $request->package_start_date ?? now();
        $company->package_end_date = $request->package_end_date ?? now()->addDays($package->package_num_days);
        $company->jobs_quota = $request->jobs_quota ?? $package->package_num_listings;
        $company->availed_jobs_quota = $request->availed_jobs_quota ?? 0;
        $company->save();

        Log::info('Company package updated by admin', [
            'admin_id' => auth()->guard('admin')->user()->id,
            'company_id' => $company->id,
            'package_id' => $package->id
        ]);

        return redirect()->route('admin.subscriptions.companies.view', $company->id)
            ->with('success', 'Company subscription updated successfully');
    }

    // ============================================
    // REPORTS
    // ============================================

    /**
     * Subscription reports
     */
    public function reports(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);

        $data = [
            'revenue_data' => $this->getRevenueReport($dateRange),
            'package_performance' => $this->getPackagePerformance($dateRange),
            'subscription_trends' => $this->getSubscriptionTrends($dateRange),
            'period' => $period,
            'date_range' => $dateRange,
        ];

        return view('admin.subscription.reports.index', $data);
    }

    /**
     * Get revenue report
     */
    private function getRevenueReport($dateRange)
    {
        $transactions = PaymentTransaction::where('status', 'success')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        $totalRevenue = $transactions->sum('amount');
        $totalTransactions = $transactions->count();
        $avgTransactionValue = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;

        // Group by payment method
        $byMethod = $transactions->groupBy('payment_method')
            ->map(function($items, $method) {
                return [
                    'method' => $method ?: 'unknown',
                    'count' => $items->count(),
                    'total' => $items->sum('amount'),
                    'formatted_total' => '₹ ' . number_format($items->sum('amount'), 2),
                ];
            })->values();

        // Daily revenue
        $daily = [];
        $current = clone $dateRange['start'];
        while ($current <= $dateRange['end']) {
            $dateStr = $current->format('Y-m-d');
            $dayRevenue = $transactions->filter(function($t) use ($dateStr) {
                return $t->created_at->format('Y-m-d') === $dateStr;
            })->sum('amount');
            
            $daily[] = [
                'date' => $dateStr,
                'revenue' => $dayRevenue,
                'formatted_revenue' => '₹ ' . number_format($dayRevenue, 2),
            ];
            $current->addDay();
        }

        return [
            'total_revenue' => $totalRevenue,
            'formatted_total_revenue' => '₹ ' . number_format($totalRevenue, 2),
            'total_transactions' => $totalTransactions,
            'avg_transaction_value' => $avgTransactionValue,
            'formatted_avg_transaction_value' => '₹ ' . number_format($avgTransactionValue, 2),
            'by_method' => $byMethod,
            'daily' => $daily,
        ];
    }

    /**
     * Get package performance
     */
    private function getPackagePerformance($dateRange)
    {
        $packages = Package::all();
        $performance = [];

        foreach ($packages as $package) {
            $requests = PaymentRequest::where('package_id', $package->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->get();
            
            $paidRequests = $requests->where('status', 'paid');
            
            $transactions = PaymentTransaction::whereIn('payment_request_id', $requests->pluck('id'))
                ->where('status', 'success')
                ->get();

            $performance[] = [
                'id' => $package->id,
                'title' => $package->package_title,
                'price' => $package->package_price,
                'formatted_price' => $package->formatted_price,
                'requests' => $requests->count(),
                'paid' => $paidRequests->count(),
                'conversion_rate' => $requests->count() > 0 
                    ? round(($paidRequests->count() / $requests->count()) * 100, 2) 
                    : 0,
                'revenue' => $transactions->sum('amount'),
                'formatted_revenue' => '₹ ' . number_format($transactions->sum('amount'), 2),
            ];
        }

        return collect($performance)->sortByDesc('revenue')->values();
    }

    /**
     * Get subscription trends
     */
    private function getSubscriptionTrends($dateRange)
    {
        $data = [];
        $current = clone $dateRange['start'];
        
        while ($current <= $dateRange['end']) {
            $dateStr = $current->format('Y-m-d');
            
            $newSubscriptions = User::where('usertype', 'company')
                ->whereNotNull('package_id')
                ->whereDate('package_start_date', $dateStr)
                ->count();
            
            $expiredSubscriptions = User::where('usertype', 'company')
                ->whereNotNull('package_id')
                ->whereDate('package_end_date', $dateStr)
                ->count();
            
            $revenue = PaymentTransaction::where('status', 'success')
                ->whereDate('created_at', $dateStr)
                ->sum('amount');

            $data[] = [
                'date' => $dateStr,
                'new_subscriptions' => $newSubscriptions,
                'expired_subscriptions' => $expiredSubscriptions,
                'revenue' => $revenue,
                'formatted_revenue' => '₹ ' . number_format($revenue, 2),
            ];
            
            $current->addDay();
        }

        return $data;
    }

    /**
     * Export reports as CSV
     */
    public function exportReports(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        $reportType = $request->get('type', 'revenue');

        $filename = $reportType . '_report_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($reportType, $dateRange) {
            $file = fopen('php://output', 'w');

            if ($reportType === 'revenue') {
                fputcsv($file, ['Date', 'Revenue', 'Transactions', 'Avg Value']);
                
                $current = clone $dateRange['start'];
                while ($current <= $dateRange['end']) {
                    $dateStr = $current->format('Y-m-d');
                    
                    $transactions = PaymentTransaction::where('status', 'success')
                        ->whereDate('created_at', $dateStr);
                    
                    $revenue = $transactions->sum('amount');
                    $count = $transactions->count();
                    $avg = $count > 0 ? $revenue / $count : 0;

                    fputcsv($file, [
                        $dateStr,
                        $revenue,
                        $count,
                        round($avg, 2),
                    ]);
                    
                    $current->addDay();
                }
            } elseif ($reportType === 'packages') {
                fputcsv($file, ['Package', 'Requests', 'Paid', 'Conversion Rate %', 'Revenue']);
                
                $performance = $this->getPackagePerformance($dateRange);
                foreach ($performance as $p) {
                    fputcsv($file, [
                        $p['title'],
                        $p['requests'],
                        $p['paid'],
                        $p['conversion_rate'],
                        $p['revenue'],
                    ]);
                }
            } elseif ($reportType === 'subscriptions') {
                fputcsv($file, ['Date', 'New Subscriptions', 'Expired Subscriptions', 'Active Subscriptions', 'Revenue']);
                
                $trends = $this->getSubscriptionTrends($dateRange);
                $activeCount = 0;
                
                foreach ($trends as $t) {
                    $activeCount += $t['new_subscriptions'] - $t['expired_subscriptions'];
                    fputcsv($file, [
                        $t['date'],
                        $t['new_subscriptions'],
                        $t['expired_subscriptions'],
                        $activeCount,
                        $t['revenue'],
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}