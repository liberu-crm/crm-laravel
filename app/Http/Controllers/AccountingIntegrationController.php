<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\AccountingIntegration;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingIntegrationController extends Controller
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function connect(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|in:quickbooks,xero',
            'credentials' => 'required|array',
        ]);

        try {
            $connection = $this->accountingService->connectPlatform($validated['platform'], $validated['credentials']);
            
            $user = Auth::user();

            AccountingIntegration::create([
                'user_id' => $user->id,
                'platform' => $validated['platform'],
                'connection_details' => $connection,
            ]);

            return response()->json(['message' => 'Successfully connected to ' . $validated['platform']]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function disconnect(AccountingIntegration $integration)
    {
        $integration->delete();
        return response()->json(['message' => 'Successfully disconnected from ' . $integration->platform]);
    }

    public function status(AccountingIntegration $integration)
    {
        return response()->json([
            'platform' => $integration->platform,
            'connected' => true,
            'last_synced' => $integration->last_synced,
        ]);
    }
}