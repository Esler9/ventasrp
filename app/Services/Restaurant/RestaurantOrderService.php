<?php

namespace App\Services\Restaurant;

use App\Models\RestaurantOrder;
use App\Models\Sale;

class RestaurantOrderService
{
    public function syncOrderStatus(int $orderId): void
    {
        if ($orderId <= 0) {
            return;
        }

        $order = RestaurantOrder::query()
            ->with('items:id,restaurant_order_id,kitchen_status')
            ->find($orderId);

        if (! $order) {
            return;
        }

        $statuses = $order->items->pluck('kitchen_status')->values();
        $activeStatuses = $statuses
            ->filter(fn (string $status) => $status !== 'canceled')
            ->values();

        if ($activeStatuses->isEmpty() || $activeStatuses->every(fn (string $status) => $status === 'served')) {
            $order->update(['status' => 'completed', 'completed_at' => now()]);
            return;
        }

        if ($activeStatuses->contains('ready') && ! $activeStatuses->contains('pending') && ! $activeStatuses->contains('preparing')) {
            $order->update(['status' => 'ready', 'completed_at' => null]);
            return;
        }

        if ($activeStatuses->contains('preparing') || ($activeStatuses->contains('ready') && $activeStatuses->contains('pending'))) {
            $order->update(['status' => 'preparing', 'completed_at' => null]);
            return;
        }

        $order->update(['status' => 'pending', 'completed_at' => null]);
    }

    public function generateSaleCode(): string
    {
        $prefix = 'R' . now()->format('Ymd');
        $lastCode = Sale::query()
            ->where('sale_code', 'like', $prefix . '-%')
            ->orderByDesc('sale_code')
            ->value('sale_code');

        if (! $lastCode) {
            return $prefix . '-0001';
        }

        $currentNumber = (int) substr((string) $lastCode, strrpos((string) $lastCode, '-') + 1);
        $nextNumber = str_pad((string) ($currentNumber + 1), 4, '0', STR_PAD_LEFT);

        return $prefix . '-' . $nextNumber;
    }
}
