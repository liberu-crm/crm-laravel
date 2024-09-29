<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportingService;

    public function __construct(ReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    public function generateContactInteractionsReport(Request $request)
    {
        $data = $this->reportingService->getContactInteractionsData($request->all());
        return view('reports.contact-interactions', compact('data'));
    }

    public function generateSalesPipelineReport(Request $request)
    {
        $data = $this->reportingService->getSalesPipelineData($request->all());
        return view('reports.sales-pipeline', compact('data'));
    }

    public function generateCustomerEngagementReport(Request $request)
    {
        $data = $this->reportingService->getCustomerEngagementData($request->all());
        return view('reports.customer-engagement', compact('data'));
    }
}