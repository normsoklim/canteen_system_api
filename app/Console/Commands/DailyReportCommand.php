<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class DailyReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily report';

    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        parent::__construct();
        $this->reportService = $reportService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating daily report...');

        // Generate report data
        $reportData = $this->reportService->dashboardSummary();

        // You can save the report to storage, send via email, etc.
        $reportPath = 'reports/daily_' . now()->format('Y-m-d') . '.json';
        Storage::put($reportPath, json_encode($reportData, JSON_PRETTY_PRINT));

        $this->info('Daily report generated: ' . $reportPath);
        
        // Here you could send the report via email to admin
        // Mail::to('admin@example.com')->send(new DailyReportMail($reportData));
    }
}