<?php

namespace App\Services;

use App\Models\AccountingIntegration;
use App\Exceptions\AccountingIntegrationException;

class AccountingService
{
    protected $quickbooksService;
    protected $xeroService;

    public function __construct(QuickBooksService $quickbooksService, XeroService $xeroService)
    {
        $this->quickbooksService = $quickbooksService;
        $this->xeroService = $xeroService;
    }

    public function syncInvoice(AccountingIntegration $integration, $invoice)
    {
        switch ($integration->platform) {
            case 'quickbooks':
                return $this->quickbooksService->syncInvoice($integration, $invoice);
            case 'xero':
                return $this->xeroService->syncInvoice($integration, $invoice);
            default:
                throw new AccountingIntegrationException("Unsupported accounting platform");
        }
    }

    public function syncPayment(AccountingIntegration $integration, $payment)
    {
        switch ($integration->platform) {
            case 'quickbooks':
                return $this->quickbooksService->syncPayment($integration, $payment);
            case 'xero':
                return $this->xeroService->syncPayment($integration, $payment);
            default:
                throw new AccountingIntegrationException("Unsupported accounting platform");
        }
    }

    public function connectPlatform(string $platform, array $credentials)
    {
        switch ($platform) {
            case 'quickbooks':
                return $this->quickbooksService->connect($credentials);
            case 'xero':
                return $this->xeroService->connect($credentials);
            default:
                throw new AccountingIntegrationException("Unsupported accounting platform");
        }
    }
}