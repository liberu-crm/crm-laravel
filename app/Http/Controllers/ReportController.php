<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use App\Services\MailChimpService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportingService;
    protected $mailChimpService;

    public function __construct(ReportingService $reportingService, MailChimpService $mailChimpService)
    {
        $this->reportingService = $reportingService;
        $this->mailChimpService = $mailChimpService;
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

    public function generateABTestResultsReport(Request $request)
    {
        $campaignId = $request->input('campaign_id');
        $data = $this->mailChimpService->getABTestResults($campaignId);
        return view('reports.ab-test-results', compact('data'));
    }
}