<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\RestaurantAccount;
use App\Models\RestaurantAccountItem;
use App\Models\RestaurantOrder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $dateFrom = (string) $request->query('date_from', now()->subDays(29)->toDateString());
        $dateTo = (string) $request->query('date_to', now()->toDateString());
        $sellerId = $request->query('seller_id');

        $salesBase = Sale::query()
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('sales.created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('sales.created_at', '<=', $dateTo))
            ->when($sellerId, fn ($query) => $query->where('sales.user_id', $sellerId));

        $salesCount = (clone $salesBase)->count();
        $revenue = (float) ((clone $salesBase)->sum('total'));
        $avgTicket = $salesCount > 0 ? round($revenue / $salesCount, 2) : 0.0;

        $itemsBase = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('sales.created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('sales.created_at', '<=', $dateTo))
            ->when($sellerId, fn ($query) => $query->where('sales.user_id', $sellerId));

        $units = (int) ((clone $itemsBase)->sum('sale_items.quantity'));
        $discounts = (float) ((clone $itemsBase)->sum('sale_items.discount_amount'));

        $salesByDay = (clone $salesBase)
            ->selectRaw('DATE(sales.created_at) as day')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(sales.total) as revenue')
            ->groupBy(DB::raw('DATE(sales.created_at)'))
            ->orderBy(DB::raw('DATE(sales.created_at)'))
            ->get()
            ->map(fn ($row) => [
                'day' => (string) $row->day,
                'orders' => (int) $row->orders,
                'revenue' => (float) $row->revenue,
            ])
            ->values();

        $topProducts = (clone $itemsBase)
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->selectRaw('sale_items.product_id as id')
            ->selectRaw("COALESCE(products.name, 'Producto eliminado') as name")
            ->selectRaw('COALESCE(products.sku, "-") as sku')
            ->selectRaw('SUM(sale_items.quantity) as units')
            ->selectRaw('SUM(sale_items.quantity * sale_items.unit_price) as revenue')
            ->selectRaw('SUM(sale_items.discount_amount) as discounts')
            ->groupBy('sale_items.product_id', 'products.name', 'products.sku')
            ->orderByDesc('units')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => (string) $row->name,
                'sku' => (string) $row->sku,
                'units' => (int) $row->units,
                'revenue' => (float) $row->revenue,
                'discounts' => (float) $row->discounts,
            ])
            ->values();

        $topSellers = (clone $salesBase)
            ->leftJoin('users', 'users.id', '=', 'sales.user_id')
            ->selectRaw('sales.user_id as id')
            ->selectRaw("COALESCE(users.name, 'Sin vendedor') as seller_name")
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(sales.total) as revenue')
            ->groupBy('sales.user_id', 'users.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'seller_name' => (string) $row->seller_name,
                'orders' => (int) $row->orders,
                'revenue' => (float) $row->revenue,
            ])
            ->values();

        $topClients = (clone $salesBase)
            ->selectRaw("COALESCE(NULLIF(customer_name, ''), 'Consumidor Final') as customer_name")
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(total) as revenue')
            ->groupBy(DB::raw("COALESCE(NULLIF(customer_name, ''), 'Consumidor Final')"))
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'customer_name' => (string) $row->customer_name,
                'orders' => (int) $row->orders,
                'revenue' => (float) $row->revenue,
            ])
            ->values();

        $paymentsByMethod = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('sales.created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('sales.created_at', '<=', $dateTo))
            ->when($sellerId, fn ($query) => $query->where('sales.user_id', $sellerId))
            ->selectRaw('sale_payments.method as method')
            ->selectRaw('SUM(sale_payments.amount) as total')
            ->selectRaw('COUNT(*) as transactions')
            ->groupBy('sale_payments.method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'method' => (string) $row->method,
                'total' => (float) $row->total,
                'transactions' => (int) $row->transactions,
            ])
            ->values();

        $sellers = User::query()
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user) => $user->hasPermission('sales.view'))
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values();

        $isRestaurantMode = (AppSetting::current()->business_mode ?: 'minorista') === 'restaurante';
        $restaurantSummary = [
            'orders_sent' => 0,
            'served_items' => 0,
            'avg_order_minutes' => 0,
            'open_accounts' => 0,
            'active_tables' => 0,
        ];

        if ($isRestaurantMode) {
            $ordersBase = RestaurantOrder::query()
                ->when($dateFrom !== '', fn ($query) => $query->whereDate('sent_at', '>=', $dateFrom))
                ->when($dateTo !== '', fn ($query) => $query->whereDate('sent_at', '<=', $dateTo))
                ->when($sellerId, fn ($query) => $query->where('created_by_user_id', $sellerId));

            $orders = (clone $ordersBase)
                ->get(['id', 'sent_at', 'completed_at']);

            $durations = $orders
                ->filter(fn (RestaurantOrder $order) => $order->sent_at && $order->completed_at && $order->completed_at->greaterThan($order->sent_at))
                ->map(fn (RestaurantOrder $order) => (int) $order->completed_at->diffInMinutes($order->sent_at));

            $restaurantSummary['orders_sent'] = $orders->count();
            $restaurantSummary['avg_order_minutes'] = $durations->isNotEmpty()
                ? round($durations->avg(), 2)
                : 0;

            $servedItemsBase = RestaurantAccountItem::query()
                ->where('kitchen_status', 'served')
                ->when($dateFrom !== '', fn ($query) => $query->whereDate('served_at', '>=', $dateFrom))
                ->when($dateTo !== '', fn ($query) => $query->whereDate('served_at', '<=', $dateTo))
                ->when($sellerId, fn ($query) => $query->whereHas('order', fn ($orderQuery) => $orderQuery->where('created_by_user_id', $sellerId)));

            $restaurantSummary['served_items'] = (int) (clone $servedItemsBase)->sum('quantity');
            $restaurantSummary['open_accounts'] = (int) RestaurantAccount::query()->where('status', 'open')->count();
            $restaurantSummary['active_tables'] = (int) RestaurantAccount::query()
                ->where('status', 'open')
                ->distinct('restaurant_table_id')
                ->count('restaurant_table_id');
        }

        return Inertia::render('Reports/Index', [
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'seller_id' => $sellerId,
            ],
            'sellers' => $sellers,
            'summary' => [
                'sales_count' => $salesCount,
                'revenue' => round($revenue, 2),
                'avg_ticket' => $avgTicket,
                'units' => $units,
                'discounts' => round($discounts, 2),
            ],
            'sales_by_day' => $salesByDay,
            'top_products' => $topProducts,
            'top_sellers' => $topSellers,
            'top_clients' => $topClients,
            'payments_by_method' => $paymentsByMethod,
            'is_restaurant_mode' => $isRestaurantMode,
            'restaurant_summary' => $restaurantSummary,
        ]);
    }
}
