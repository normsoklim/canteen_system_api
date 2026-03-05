<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Exports\SalesReportExport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Dashboard analytics report
     */
    public function dashboard(): JsonResponse
    {
        return response()->json($this->reportService->cachedDashboard());
    }

    /**
     * Sales and profit report
     */
    public function profit(Request $request)
    {
        $start = $request->input('start', now()->startOfMonth());
        $end = $request->input('end', now());

        $result = $this->reportService->salesProfit($start, $end);

        return response()->json($result);
    }

    /**
     * Category performance report
     */
    public function category()
    {
        $result = $this->reportService->categoryPerformance();

        return response()->json($result);
    }

    /**
     * Hourly sales report
     */
    public function hourly()
    {
        $result = $this->reportService->hourlySales();

        return response()->json($result);
    }

    /**
     * Staff performance report
     */
    public function staff()
    {
        $result = $this->reportService->staffPerformance();

        return response()->json($result);
    }

    /**
     * Export sales report to Excel
     */
    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start', now()->startOfMonth());
        $endDate = $request->input('end', now());

        return Excel::download(
            new SalesReportExport($startDate, $endDate),
            'sales_report_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export sales report to PDF
     */
    public function exportPdf(Request $request)
    {
        $startDate = $request->input('start', now()->startOfMonth());
        $endDate = $request->input('end', now());

        $orders = $this->reportService->getOrdersForExport($startDate, $endDate);

        $pdf = Pdf::loadView('reports.sales', [
            'orders' => $orders,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        return $pdf->download('sales_report_' . now()->format('Y-m-d') . '.pdf');
    }
}
