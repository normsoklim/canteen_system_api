<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesReportExport implements FromCollection, WithHeadings
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        // Ensure dates are properly formatted for database queries
        $this->startDate = $startDate ? (is_string($startDate) ? $startDate : $startDate->format('Y-m-d H:i:s')) : now()->startOfMonth()->format('Y-m-d H:i:s');
        $this->endDate = $endDate ? (is_string($endDate) ? $endDate : $endDate->format('Y-m-d H:i:s')) : now()->format('Y-m-d H:i:s');
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return DB::table('orders')
            ->select('id', 'user_id', 'total_amount', 'order_status', 'created_at')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('order_status', 'Completed')
            ->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'User ID',
            'Total Amount',
            'Order Status',
            'Created At',
        ];
    }
}