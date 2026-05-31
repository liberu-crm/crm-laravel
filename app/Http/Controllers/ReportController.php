<?php

namespace App\Http\Controllers;

use App\Services\MailChimpService;
use App\Services\ReportingService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(protected \App\Services\ReportingService $reportingService, protected \App\Services\MailChimpService $mailChimpService)
    {
    }

    public function generateContactInteractionsReport(Request $request): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $data = $this->reportingService->getContactInteractionsData($request->all());

        return view('reports.contact-interactions', ['data' => $data]);
    }

    public function generateSalesPipelineReport(Request $request): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $data = $this->reportingService->getSalesPipelineData($request->all());

        return view('reports.sales-pipeline', ['data' => $data]);
    }

    public function generateCustomerEngagementReport(Request $request): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $data = $this->reportingService->getCustomerEngagementData($request->all());

        return view('reports.customer-engagement', ['data' => $data]);
    }

    public function generateABTestResultsReport(Request $request): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $campaignId = $request->input('campaign_id');
        $data = $this->mailChimpService->getABTestResults($campaignId);

        return view('reports.ab-test-results', ['data' => $data]);
    }

    public function generateEmailCampaignReport(Request $request): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $campaignId = $request->input('campaign_id');
        $data = $this->mailChimpService->getCampaignReport($campaignId);

        return view('reports.email-campaign-performance', ['data' => $data]);
    }
}
