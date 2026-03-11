<?php

namespace Src\Infrastructure\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Src\Domains\Admin\UseCases\GetAllUsersUseCase;
use Src\Domains\Admin\UseCases\GetAllTenantsUseCase;
use Src\Domains\Admin\UseCases\ToggleUserStatusUseCase;
use Src\Domains\Admin\UseCases\ToggleTenantStatusUseCase;

class AdminController
{
    private GetAllUsersUseCase $getAllUsersUseCase;
    private GetAllTenantsUseCase $getAllTenantsUseCase;
    private ToggleUserStatusUseCase $toggleUserStatusUseCase;
    private ToggleTenantStatusUseCase $toggleTenantStatusUseCase;

    public function __construct(
        GetAllUsersUseCase $getAllUsersUseCase,
        GetAllTenantsUseCase $getAllTenantsUseCase,
        ToggleUserStatusUseCase $toggleUserStatusUseCase,
        ToggleTenantStatusUseCase $toggleTenantStatusUseCase
    ) {
        $this->getAllUsersUseCase = $getAllUsersUseCase;
        $this->getAllTenantsUseCase = $getAllTenantsUseCase;
        $this->toggleUserStatusUseCase = $toggleUserStatusUseCase;
        $this->toggleTenantStatusUseCase = $toggleTenantStatusUseCase;
    }

    /**
     * Display the ROOT dashboard with global statistics.
     */
    public function dashboard(Request $request)
    {
        $period = $request->input('period', '30'); // 7, 30, 90, all
        $from = $request->input('from');
        $to = $request->input('to');

        // Calculate date range
        if ($from && $to) {
            $startDate = \Carbon\Carbon::parse($from)->startOfDay();
            $endDate = \Carbon\Carbon::parse($to)->endOfDay();
        } else {
            switch ($period) {
                case '7':
                    $startDate = now()->subDays(7)->startOfDay();
                    $endDate = now()->endOfDay();
                    break;
                case '90':
                    $startDate = now()->subDays(90)->startOfDay();
                    $endDate = now()->endOfDay();
                    break;
                case 'all':
                    $startDate = null;
                    $endDate = null;
                    break;
                default: // 30 days
                    $startDate = now()->subDays(30)->startOfDay();
                    $endDate = now()->endOfDay();
            }
        }

        // 1. KPI Cards - Vue d'ensemble globale
        $totalTenants = \App\Models\Tenant::count();
        $activeTenants = \App\Models\Tenant::where('is_active', true)->count();
        $inactiveTenants = $totalTenants - $activeTenants;

        $totalUsers = \App\Models\User::where('type', '!=', 'ROOT')->count();
        $activeUsers = \App\Models\User::where('type', '!=', 'ROOT')->where('is_active', true)->count();
        $inactiveUsers = $totalUsers - $activeUsers;

        // Total produits de toutes les boutiques (tous modules)
        $totalProducts = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('pharmacy_products')) {
            $totalProducts += \Illuminate\Support\Facades\DB::table('pharmacy_products')->count();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('gc_products')) {
            $totalProducts += \Illuminate\Support\Facades\DB::table('gc_products')->count();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('quincaillerie_products')) {
            $totalProducts += \Illuminate\Support\Facades\DB::table('quincaillerie_products')->count();
        }
        // Legacy products table
        if (\Illuminate\Support\Facades\Schema::hasTable('products')) {
            $totalProducts += \Illuminate\Support\Facades\DB::table('products')->count();
        }

        // 2. Chiffre d'affaires global (toutes les ventes de tous les modules)
        $revenueQuery = function ($table, $amountColumn, $statusColumn = 'status', $statusValue = 'completed') use ($startDate, $endDate) {
            $query = \Illuminate\Support\Facades\DB::table($table)
                ->where($statusColumn, $statusValue);
            
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
            
            return $query->sum($amountColumn) ?? 0;
        };

        $totalRevenue = 0;
        
        // Pharmacy sales
        if (\Illuminate\Support\Facades\Schema::hasTable('pharmacy_sales')) {
            $totalRevenue += $revenueQuery('pharmacy_sales', 'total_amount', 'status', 'COMPLETED');
        }
        
        // Commerce sales
        if (\Illuminate\Support\Facades\Schema::hasTable('gc_sales')) {
            $totalRevenue += $revenueQuery('gc_sales', 'total_amount', 'status', 'completed');
        }
        
        // Hardware sales
        if (\Illuminate\Support\Facades\Schema::hasTable('quincaillerie_sales')) {
            $totalRevenue += $revenueQuery('quincaillerie_sales', 'total_amount', 'status', 'completed');
        }
        
        // Legacy sales table
        if (\Illuminate\Support\Facades\Schema::hasTable('sales')) {
            $totalRevenue += $revenueQuery('sales', 'total', 'status', 'completed');
        }

        // Total produits (tous modules)
        $totalProducts = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('pharmacy_products')) {
            $totalProducts += \Illuminate\Support\Facades\DB::table('pharmacy_products')->count();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('gc_products')) {
            $totalProducts += \Illuminate\Support\Facades\DB::table('gc_products')->count();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('quincaillerie_products')) {
            $totalProducts += \Illuminate\Support\Facades\DB::table('quincaillerie_products')->count();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('products')) {
            $totalProducts += \Illuminate\Support\Facades\DB::table('products')->count();
        }

        // 3. Répartition par module
        $moduleStats = [
            'pharmacy' => [
                'tenants' => \App\Models\Tenant::where('sector', 'pharmacy')->count(),
                'users' => \App\Models\User::whereHas('tenant', function ($q) {
                    $q->where('sector', 'pharmacy');
                })->where('type', '!=', 'ROOT')->count(),
                'revenue' => \Illuminate\Support\Facades\Schema::hasTable('pharmacy_sales') 
                    ? $revenueQuery('pharmacy_sales', 'total_amount', 'status', 'COMPLETED')
                    : 0,
            ],
            'commerce' => [
                'tenants' => \App\Models\Tenant::where('sector', 'commerce')->count(),
                'users' => \App\Models\User::whereHas('tenant', function ($q) {
                    $q->where('sector', 'commerce');
                })->where('type', '!=', 'ROOT')->count(),
                'revenue' => \Illuminate\Support\Facades\Schema::hasTable('gc_sales')
                    ? $revenueQuery('gc_sales', 'total_amount', 'status', 'completed')
                    : 0,
            ],
            'hardware' => [
                'tenants' => \App\Models\Tenant::where('sector', 'hardware')->count(),
                'users' => \App\Models\User::whereHas('tenant', function ($q) {
                    $q->where('sector', 'hardware');
                })->where('type', '!=', 'ROOT')->count(),
                'revenue' => \Illuminate\Support\Facades\Schema::hasTable('quincaillerie_sales')
                    ? $revenueQuery('quincaillerie_sales', 'total_amount', 'status', 'completed')
                    : 0,
            ],
            'ecommerce' => [
                'tenants' => \App\Models\Tenant::where('sector', 'ecommerce')->count(),
                'users' => \App\Models\User::whereHas('tenant', function ($q) {
                    $q->where('sector', 'ecommerce');
                })->where('type', '!=', 'ROOT')->count(),
                'revenue' => 0, // TODO: Add ecommerce orders table
            ],
        ];

        // 4. Graphiques - Tendances (30 derniers jours)
        $trendsData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayStart = \Carbon\Carbon::parse($date)->startOfDay();
            $dayEnd = \Carbon\Carbon::parse($date)->endOfDay();
            
            $dayRevenue = 0;
            if (\Illuminate\Support\Facades\Schema::hasTable('pharmacy_sales')) {
                $dayRevenue += \Illuminate\Support\Facades\DB::table('pharmacy_sales')
                    ->where('status', 'COMPLETED')
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->sum('total_amount') ?? 0;
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('gc_sales')) {
                $dayRevenue += \Illuminate\Support\Facades\DB::table('gc_sales')
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->sum('total_amount') ?? 0;
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('quincaillerie_sales')) {
                $dayRevenue += \Illuminate\Support\Facades\DB::table('quincaillerie_sales')
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->sum('total_amount') ?? 0;
            }
            
            $trendsData[] = [
                'date' => $date,
                'revenue' => (float) $dayRevenue,
                'tenants' => \App\Models\Tenant::whereDate('created_at', $date)->count(),
                'users' => \App\Models\User::where('type', '!=', 'ROOT')->whereDate('created_at', $date)->count(),
            ];
        }

        // 5. Alertes système
        $alerts = [];
        
        // Tenants inactifs depuis > 30 jours
        $inactiveTenants = \App\Models\Tenant::where('is_active', false)
            ->where('updated_at', '<', now()->subDays(30))
            ->count();
        if ($inactiveTenants > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Tenants inactifs',
                'message' => "{$inactiveTenants} tenant(s) inactif(s) depuis plus de 30 jours",
                'count' => $inactiveTenants,
            ];
        }
        
        // Utilisateurs en attente
        $pendingUsers = \App\Models\User::where('type', '!=', 'ROOT')
            ->where('status', 'pending')
            ->count();
        if ($pendingUsers > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Utilisateurs en attente',
                'message' => "{$pendingUsers} utilisateur(s) en attente de validation",
                'count' => $pendingUsers,
            ];
        }

        // 6. Activité récente
        $recentActivity = [];
        
        // Nouveaux tenants (7 derniers jours)
        $recentTenants = \App\Models\Tenant::where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'sector', 'created_at']);
        
        // Nouveaux utilisateurs (7 derniers jours)
        $recentUsers = \App\Models\User::where('type', '!=', 'ROOT')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'created_at']);
        
        // Connexions récentes
        $recentLogins = \App\Models\User::where('type', '!=', 'ROOT')
            ->whereNotNull('last_login_at')
            ->where('last_login_at', '>=', now()->subDays(7))
            ->orderBy('last_login_at', 'desc')
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'last_login_at']);

        // 7. Top tenants (par CA)
        $topTenants = [];
        $tenantRevenues = [];
        
        // Calculer le CA par tenant
        $tenants = \App\Models\Tenant::all();
        foreach ($tenants as $tenant) {
            $tenantRevenue = 0;
            
            // Pharmacy
            if (\Illuminate\Support\Facades\Schema::hasTable('pharmacy_sales')) {
                $shops = \App\Models\Shop::where('tenant_id', $tenant->id)->pluck('id');
                if ($shops->isNotEmpty()) {
                    $tenantRevenue += \Illuminate\Support\Facades\DB::table('pharmacy_sales')
                        ->whereIn('shop_id', $shops)
                        ->where('status', 'COMPLETED')
                        ->sum('total_amount') ?? 0;
                }
            }
            
            // Commerce
            if (\Illuminate\Support\Facades\Schema::hasTable('gc_sales')) {
                $shops = \App\Models\Shop::where('tenant_id', $tenant->id)->pluck('id');
                if ($shops->isNotEmpty()) {
                    $tenantRevenue += \Illuminate\Support\Facades\DB::table('gc_sales')
                        ->whereIn('shop_id', $shops)
                        ->where('status', 'completed')
                        ->sum('total_amount') ?? 0;
                }
            }
            
            if ($tenantRevenue > 0) {
                $tenantRevenues[] = [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'sector' => $tenant->sector,
                    'revenue' => (float) $tenantRevenue,
                ];
            }
        }
        
        // Trier par CA décroissant
        usort($tenantRevenues, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        $topTenants = array_slice($tenantRevenues, 0, 10);

        return Inertia::render('Admin/RootDashboard', [
            'kpis' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'inactive_tenants' => $inactiveTenants,
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'total_revenue' => (float) $totalRevenue,
                'total_products' => (int) $totalProducts,
            ],
            'module_stats' => $moduleStats,
            'trends' => $trendsData,
            'alerts' => $alerts,
            'recent_activity' => [
                'tenants' => $recentTenants->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'name' => $t->name,
                        'sector' => $t->sector,
                        'created_at' => $t->created_at !== null ? $t->created_at->toDateTimeString() : null,
                    ];
                })->toArray(),
                'users' => $recentUsers->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                        'email' => $u->email,
                        'created_at' => $u->created_at !== null ? $u->created_at->toDateTimeString() : null,
                    ];
                })->toArray(),
                'logins' => $recentLogins->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                        'email' => $u->email,
                        'last_login_at' => $u->last_login_at !== null ? $u->last_login_at->toDateTimeString() : null,
                    ];
                })->toArray(),
            ],
            'top_tenants' => $topTenants,
            'period' => $period,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Display the tenant selection page.
     */
    public function selectTenant()
    {
        $tenants = $this->getAllTenantsUseCase->execute();

        return Inertia::render('Admin/Tenants/Select', [
            'tenants' => $tenants
        ]);
    }

    /**
     * Display the tenant dashboard.
     */
    public function tenantDashboard($id)
    {
        // For now, we'll just render the view with the ID
        return Inertia::render('Admin/Tenants/Dashboard', [
            'tenantId' => $id
        ]);
    }

    /**
     * Manage tenants page.
     */
    public function manageTenants()
    {
        $tenants = $this->getAllTenantsUseCase->execute();

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants
        ]);
    }

    /**
     * Manage users page.
     */
    public function manageUsers()
    {
        $users = $this->getAllUsersUseCase->execute();
        
        // Récupérer tous les rôles disponibles (globaux + par tenant)
        // Les rôles globaux (tenant_id = null) sont créés par le seeder et disponibles pour tous
        $roles = \App\Models\Role::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'tenant_id']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    /**
     * Show user details page.
     */
    public function showUser($id)
    {
        $userModel = \App\Models\User::with(['tenant', 'roles'])->findOrFail($id);
        
        // Gérer first_name/last_name ou name
        $firstName = $userModel->first_name;
        $lastName = $userModel->last_name;
        
        // Si first_name/last_name n'existent pas, utiliser name
        if (!$firstName && !$lastName && $userModel->name) {
            $nameParts = explode(' ', $userModel->name, 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';
        }
        
        // Préparer les données de l'utilisateur
        $userData = [
            'id' => $userModel->id,
            'email' => $userModel->email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $userModel->name ?? ($firstName . ' ' . $lastName),
            'type' => $userModel->type ?? 'MERCHANT',
            'status' => $userModel->status ?? 'pending',
            'tenant_id' => $userModel->tenant_id,
            'is_active' => $userModel->is_active ?? true,
            'last_login_at' => $userModel->last_login_at ? $userModel->last_login_at->toDateTimeString() : null,
            'created_at' => $userModel->created_at ? $userModel->created_at->toDateTimeString() : null,
            'updated_at' => $userModel->updated_at ? $userModel->updated_at->toDateTimeString() : null,
            'roles' => $userModel->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                ];
            })->toArray(),
        ];
        
        // Préparer les données du tenant (incluant les données d'onboarding)
        $tenantData = null;
        if ($userModel->tenant) {
            $tenant = $userModel->tenant;
            $tenantData = [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'code' => $tenant->code,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
                'city' => $tenant->city,
                'country' => $tenant->country,
                'sector' => $tenant->sector,
                'business_type' => $tenant->business_type,
                'legal_form' => $tenant->legal_form,
                'registration_number' => $tenant->registration_number,
                'tax_id' => $tenant->tax_id,
                'idnat' => $tenant->idnat ?? null,
                'rccm' => $tenant->rccm ?? null,
                'currency_code' => $tenant->currency_code,
                'timezone' => $tenant->timezone,
                'locale' => $tenant->locale,
                'status' => $tenant->status,
                'created_at' => $tenant->created_at ? $tenant->created_at->toDateTimeString() : null,
                'updated_at' => $tenant->updated_at ? $tenant->updated_at->toDateTimeString() : null,
            ];
        }

        return Inertia::render('Admin/Users/Show', [
            'user' => $userData,
            'tenant' => $tenantData,
        ]);
    }

    /**
     * Toggle tenant status.
     */
    public function toggleTenant(Request $request, $id)
    {
        $this->toggleTenantStatusUseCase->execute($id);

        return response()->json(['message' => 'Status updated successfully']);
    }

    /**
     * Toggle user status.
     */
    public function toggleUser(Request $request, $id)
    {
        $this->toggleUserStatusUseCase->execute($id);

        return response()->json(['message' => 'Status updated successfully']);
    }

    /**
     * Export dashboard data as PDF.
     */
    public function exportPdf(Request $request)
    {
        // Récupérer les mêmes données que le dashboard
        $period = $request->input('period', '30');
        $from = $request->input('from');
        $to = $request->input('to');

        if ($from && $to) {
            $startDate = \Carbon\Carbon::parse($from)->startOfDay();
            $endDate = \Carbon\Carbon::parse($to)->endOfDay();
        } else {
            switch ($period) {
                case '7':
                    $startDate = now()->subDays(7)->startOfDay();
                    $endDate = now()->endOfDay();
                    break;
                case '90':
                    $startDate = now()->subDays(90)->startOfDay();
                    $endDate = now()->endOfDay();
                    break;
                case 'all':
                    $startDate = null;
                    $endDate = null;
                    break;
                default:
                    $startDate = now()->subDays(30)->startOfDay();
                    $endDate = now()->endOfDay();
            }
        }

        $totalTenants = \App\Models\Tenant::count();
        $activeTenants = \App\Models\Tenant::where('is_active', true)->count();
        $totalUsers = \App\Models\User::where('type', '!=', 'ROOT')->count();
        $activeUsers = \App\Models\User::where('type', '!=', 'ROOT')->where('is_active', true)->count();

        $revenueQuery = function ($table, $amountColumn, $statusColumn = 'status', $statusValue = 'completed') use ($startDate, $endDate) {
            $query = \Illuminate\Support\Facades\DB::table($table)
                ->where($statusColumn, $statusValue);
            
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
            
            return $query->sum($amountColumn) ?? 0;
        };

        $totalRevenue = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('pharmacy_sales')) {
            $totalRevenue += $revenueQuery('pharmacy_sales', 'total_amount', 'status', 'COMPLETED');
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('gc_sales')) {
            $totalRevenue += $revenueQuery('gc_sales', 'total_amount', 'status', 'completed');
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('quincaillerie_sales')) {
            $totalRevenue += $revenueQuery('quincaillerie_sales', 'total_amount', 'status', 'completed');
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('sales')) {
            $totalRevenue += $revenueQuery('sales', 'total', 'status', 'completed');
        }

        $data = [
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'generated_at' => now()->format('d/m/Y H:i'),
            'kpis' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'total_revenue' => (float) $totalRevenue,
                'total_products' => (int) $totalProducts,
            ],
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.dashboard.pdf', $data);
        $pdf->setPaper('a4', 'landscape');
        $filename = 'dashboard_root_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Export dashboard data as Excel.
     */
    public function exportExcel(Request $request)
    {
        $period = $request->input('period', '30');
        $from = $request->input('from');
        $to = $request->input('to');

        if ($from && $to) {
            $startDate = \Carbon\Carbon::parse($from)->startOfDay();
            $endDate = \Carbon\Carbon::parse($to)->endOfDay();
        } else {
            switch ($period) {
                case '7':
                    $startDate = now()->subDays(7)->startOfDay();
                    $endDate = now()->endOfDay();
                    break;
                case '90':
                    $startDate = now()->subDays(90)->startOfDay();
                    $endDate = now()->endOfDay();
                    break;
                case 'all':
                    $startDate = null;
                    $endDate = null;
                    break;
                default:
                    $startDate = now()->subDays(30)->startOfDay();
                    $endDate = now()->endOfDay();
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dashboard ROOT');

        // Headers
        $headers = ['Métrique', 'Valeur'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        // Data
        $totalTenants = \App\Models\Tenant::count();
        $activeTenants = \App\Models\Tenant::where('is_active', true)->count();
        $totalUsers = \App\Models\User::where('type', '!=', 'ROOT')->count();
        $activeUsers = \App\Models\User::where('type', '!=', 'ROOT')->where('is_active', true)->count();
        
        // Total produits (tous modules)
        $totalProducts = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('pharmacy_products')) {
            $totalProducts += \Illuminate\Support\Facades\DB::table('pharmacy_products')->count();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('gc_products')) {
            $totalProducts += \Illuminate\Support\Facades\DB::table('gc_products')->count();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('quincaillerie_products')) {
            $totalProducts += \Illuminate\Support\Facades\DB::table('quincaillerie_products')->count();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('products')) {
            $totalProducts += \Illuminate\Support\Facades\DB::table('products')->count();
        }

        $revenueQuery = function ($table, $amountColumn, $statusColumn = 'status', $statusValue = 'completed') use ($startDate, $endDate) {
            $query = \Illuminate\Support\Facades\DB::table($table)
                ->where($statusColumn, $statusValue);
            
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
            
            return $query->sum($amountColumn) ?? 0;
        };

        $totalRevenue = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('pharmacy_sales')) {
            $totalRevenue += $revenueQuery('pharmacy_sales', 'total_amount', 'status', 'COMPLETED');
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('gc_sales')) {
            $totalRevenue += $revenueQuery('gc_sales', 'total_amount', 'status', 'completed');
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('quincaillerie_sales')) {
            $totalRevenue += $revenueQuery('quincaillerie_sales', 'total_amount', 'status', 'completed');
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('sales')) {
            $totalRevenue += $revenueQuery('sales', 'total', 'status', 'completed');
        }

        $data = [
            ['Total Tenants', $totalTenants],
            ['Tenants Actifs', $activeTenants],
            ['Total Utilisateurs', $totalUsers],
            ['Utilisateurs Actifs', $activeUsers],
            ['Chiffre d\'affaires', number_format($totalRevenue, 2, ',', ' ')],
            ['Total Produits', $totalProducts],
        ];

        $sheet->fromArray($data, null, 'A2');
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        $filename = 'dashboard_root_' . now()->format('Ymd_His') . '.xlsx';
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}