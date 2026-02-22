<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $settings = AppSetting::current();

        $now = now();
        $startToday = $now->copy()->startOfDay();
        $endToday = $now->copy()->endOfDay();
        $startYesterday = $now->copy()->subDay()->startOfDay();
        $endYesterday = $now->copy()->subDay()->endOfDay();
        $startMonth = $now->copy()->startOfMonth();
        $endMonth = $now->copy()->endOfMonth();
        $startPreviousMonth = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $endPreviousMonth = $startPreviousMonth->copy()->endOfMonth();

        $salesToday = (float) Sale::query()
            ->whereBetween('created_at', [$startToday, $endToday])
            ->sum('total');

        $salesYesterday = (float) Sale::query()
            ->whereBetween('created_at', [$startYesterday, $endYesterday])
            ->sum('total');

        $salesMonth = (float) Sale::query()
            ->whereBetween('created_at', [$startMonth, $endMonth])
            ->sum('total');

        $salesPreviousMonth = (float) Sale::query()
            ->whereBetween('created_at', [$startPreviousMonth, $endPreviousMonth])
            ->sum('total');

        $expensesToday = (float) CashMovement::query()
            ->where('type', 'expense')
            ->whereBetween('created_at', [$startToday, $endToday])
            ->sum('amount');

        $expensesYesterday = (float) CashMovement::query()
            ->where('type', 'expense')
            ->whereBetween('created_at', [$startYesterday, $endYesterday])
            ->sum('amount');

        $utilityToday = max(0, $salesToday - $expensesToday);
        $utilityYesterday = max(0, $salesYesterday - $expensesYesterday);

        $bankBalance = (float) SalePayment::query()
            ->whereIn('method', ['card', 'transfer'])
            ->whereBetween('created_at', [$startMonth, $endMonth])
            ->sum('amount');

        $lowStockProducts = Product::query()
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->orderBy('name')
            ->limit(3)
            ->get(['id', 'name', 'stock']);

        $lowStockCount = Product::query()
            ->where('stock', '<=', 5)
            ->count();

        $monthlyGoal = $salesPreviousMonth > 0
            ? round($salesPreviousMonth, 2)
            : max(10000, round($salesMonth * 1.2, 2));

        $monthProgress = $monthlyGoal > 0
            ? min(100, round(($salesMonth / $monthlyGoal) * 100, 1))
            : 0;

        $branchName = CashSession::query()
            ->with('register:id,branch_name')
            ->where('status', 'open')
            ->where('user_id', $user?->id)
            ->latest('opened_at')
            ->first()?->register?->branch_name;

        if (! is_string($branchName) || trim($branchName) === '') {
            $branchName = CashRegister::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->value('branch_name') ?? 'Sucursal Principal';
        }

        $permissions = $user?->permissions() ?? [];
        $can = fn (string $permission): bool => in_array('*', $permissions, true)
            || in_array($permission, $permissions, true);

        $quickActions = collect([
            [
                'key' => 'pos',
                'title' => 'Realizar Venta (POS)',
                'subtitle' => 'Nueva orden de facturación rápida',
                'href' => '/pos',
                'icon' => 'fa-solid fa-wallet',
                'icon_bg' => 'bg-sky-500/20 text-sky-300',
                'enabled' => $can('pos.view'),
            ],
            [
                'key' => 'expense',
                'title' => 'Registrar Gasto',
                'subtitle' => 'Capturar recibo o pago a proveedor',
                'href' => '/admin/expenses',
                'icon' => 'fa-solid fa-receipt',
                'icon_bg' => 'bg-amber-500/20 text-amber-300',
                'enabled' => $can('expenses.view') || $can('expenses.create'),
            ],
            [
                'key' => 'stock',
                'title' => 'Ver Inventario Bajo',
                'subtitle' => $lowStockCount > 0
                    ? "{$lowStockCount} productos requieren atención"
                    : 'Sin alertas de inventario en este momento',
                'href' => '/admin/products',
                'icon' => 'fa-solid fa-box-open',
                'icon_bg' => 'bg-rose-500/20 text-rose-300',
                'enabled' => $can('products.view'),
            ],
            [
                'key' => 'close-cash',
                'title' => 'Cierre de Caja',
                'subtitle' => 'Finalizar turno y arquear',
                'href' => '/admin/cash',
                'icon' => 'fa-solid fa-lock',
                'icon_bg' => 'bg-emerald-500/20 text-emerald-300',
                'enabled' => $can('cash.view') || $can('cash.close'),
            ],
        ])
            ->filter(fn (array $action) => $action['enabled'])
            ->values();

        $recentSales = Sale::query()
            ->latest('created_at')
            ->limit(4)
            ->get(['id', 'sale_code', 'customer_name', 'total', 'created_at'])
            ->map(fn (Sale $sale) => [
                'id' => 'sale-' . $sale->id,
                'title' => 'Venta #' . ($sale->sale_code ?: $sale->id),
                'subtitle' => $sale->customer_name ? 'Cliente: ' . $sale->customer_name : 'Consumidor Final',
                'amount' => (float) $sale->total,
                'amount_prefix' => '+',
                'href' => '/admin/sales',
                'created_at' => optional($sale->created_at)->toIso8601String(),
            ]);

        $recentEntries = InventoryMovement::query()
            ->with('product:id,name')
            ->where('quantity', '>', 0)
            ->where('type', '!=', 'sale')
            ->latest('created_at')
            ->limit(2)
            ->get()
            ->map(fn (InventoryMovement $movement) => [
                'id' => 'inventory-' . $movement->id,
                'title' => 'Recepción Mercancía',
                'subtitle' => ($movement->product?->name ?? 'Producto')
                    . ' · +' . $movement->quantity . ' unidades',
                'amount' => null,
                'amount_prefix' => '',
                'href' => '/admin/products',
                'created_at' => optional($movement->created_at)->toIso8601String(),
            ]);

        $recentActivity = $recentSales
            ->concat($recentEntries)
            ->sortByDesc(fn (array $item) => (string) $item['created_at'])
            ->take(5)
            ->values();

        return Inertia::render('Admin/Dashboard', [
            'branch_name' => $branchName,
            'financial_summary' => [
                'sales_today' => round($salesToday, 2),
                'sales_today_trend' => $this->percentageChange($salesToday, $salesYesterday),
                'utility_today' => round($utilityToday, 2),
                'utility_today_trend' => $this->percentageChange($utilityToday, $utilityYesterday),
                'sales_month' => round($salesMonth, 2),
                'sales_month_goal' => round($monthlyGoal, 2),
                'sales_month_progress' => $monthProgress,
                'sales_month_trend' => $this->percentageChange($salesMonth, $salesPreviousMonth),
                'bank_balance' => round($bankBalance, 2),
                'bank_balance_label' => 'Disponible',
            ],
            'currency' => [
                'code' => $settings->currency_code ?: 'GTQ',
                'symbol' => $settings->currency_symbol ?: 'Q',
            ],
            'quick_actions' => $quickActions,
            'recent_activity' => $recentActivity,
            'low_stock_products' => $lowStockProducts->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => (int) $product->stock,
            ])->values(),
        ]);
    }

    private function percentageChange(float $current, float $previous): float
    {
        if (abs($previous) < 0.001) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
