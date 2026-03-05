<?php

namespace App\Services;

use App\Repositories\ReportRepository;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    protected ReportRepository $reportRepository;

    public function __construct(ReportRepository $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }

    /**
     * Dashboard summary report
     */
    public function dashboardSummary()
    {
        return $this->reportRepository->getDashboardSummary();
    }

    /**
     * Sales and profit report
     */
    public function salesProfit($start, $end)
    {
        return $this->reportRepository->getSalesProfit($start, $end);
    }

    /**
     * Category performance report
     */
    public function categoryPerformance()
    {
        return $this->reportRepository->getCategoryPerformance();
    }

    /**
     * Hourly sales report
     */
    public function hourlySales()
    {
        return $this->reportRepository->getHourlySales();
    }

    /**
     * Staff performance report
     */
    public function staffPerformance()
    {
        return $this->reportRepository->getStaffPerformance();
    }

    /**
     * Cached dashboard report
     */
    public function cachedDashboard()
    {
        return Cache::remember(
            'dashboard_report',
            300, // 5 minutes
            fn() => $this->dashboardSummary()
        );
    }

    /**
     * Get orders for export
     */
    public function getOrdersForExport($startDate, $endDate)
    {
        return $this->reportRepository->getOrdersForExport($startDate, $endDate);
    }
}