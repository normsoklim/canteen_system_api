<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ReportRepository
{
    /**
     * Get dashboard summary data
     */
    public function getDashboardSummary()
    {
        return [
            'total_sales' => DB::table('orders')
                ->where('order_status', 'Completed')
                ->sum('total_amount'),

            'total_orders' => DB::table('orders')->count(),

            'total_customers' => DB::table('users')
                ->where('role', 'student')
                ->count(),

            'today_sales' => DB::table('orders')
                ->whereDate('created_at', today())
                ->where('order_status', 'Completed')
                ->sum('total_amount'),
        ];
    }

    /**
     * Get sales and profit data
     */
    public function getSalesProfit($start, $end)
    {
        return DB::table('order_detail')
            ->join('orders', 'orders.id', '=', 'order_detail.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_detail.menu_item_id')
            ->selectRaw('
                SUM(order_detail.quantity * order_detail.unit_price) as revenue,
                SUM(order_detail.quantity * COALESCE(menu_items.cost_price, 0)) as cost,
                SUM(order_detail.quantity * (order_detail.unit_price - COALESCE(menu_items.cost_price, 0))) as profit
            ')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.order_status', 'Completed')
            ->first();
    }

    /**
     * Get category performance data
     */
    public function getCategoryPerformance()
    {
        return DB::table('order_detail')
            ->join('menu_items', 'menu_items.id', '=', 'order_detail.menu_item_id')
            ->join('categories', 'categories.id', '=', 'menu_items.category_id')
            ->select(
                'categories.category_name',
                DB::raw('SUM(order_detail.quantity) as total_sold'),
                DB::raw('SUM(order_detail.quantity * order_detail.unit_price) as revenue')
            )
            ->groupBy('categories.category_name')
            ->orderByDesc('revenue')
            ->get();
    }

    /**
     * Get hourly sales data
     */
    public function getHourlySales()
    {
        return DB::table('orders')
            ->selectRaw('
                HOUR(created_at) as hour,
                COUNT(*) as orders,
                SUM(total_amount) as revenue
            ')
            ->where('order_status', 'Completed')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    /**
     * Get staff performance data
     */
    public function getStaffPerformance()
    {
        return DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->select(
                'users.full_name',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('users.full_name')
            ->orderByDesc('revenue')
            ->get();
    }

    /**
     * Get orders for export
     */
    public function getOrdersForExport($startDate, $endDate)
    {
        // Ensure dates are properly formatted for database queries
        $startDate = is_string($startDate) ? $startDate : $startDate->format('Y-m-d H:i:s');
        $endDate = is_string($endDate) ? $endDate : $endDate->format('Y-m-d H:i:s');

        return DB::table('orders')
            ->select('id', 'user_id', 'total_amount', 'order_status', 'created_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('order_status', 'Completed')
            ->get();
    }
}