<?php

namespace App\Services;

use App\Exceptions\AccountingIntegrationException;
use App\Models\AccountingIntegration;

class AccountingService
{
    public function __construct(protected \App\Services\QuickBooksService $quickbooksService, protected \App\Services\XeroService $xeroService)
    {
    }

    public function syncInvoice(AccountingIntegration $integration, $invoice)
    {
        return match ($integration->platform) {
            'quickbooks' => $this->quickbooksService->syncInvoice($integration, $invoice),
            'xero' => $this->xeroService->syncInvoice($integration, $invoice),
            default => throw new AccountingIntegrationException('Unsupported accounting platform'),
        };
    }

    public function syncPayment(AccountingIntegration $integration, $payment)
    {
        return match ($integration->platform) {
            'quickbooks' => $this->quickbooksService->syncPayment($integration, $payment),
            'xero' => $this->xeroService->syncPayment($integration, $payment),
            default => throw new AccountingIntegrationException('Unsupported accounting platform'),
        };
    }

    public function connectPlatform(string $platform, array $credentials)
    {
        return match ($platform) {
            'quickbooks' => $this->quickbooksService->connect($credentials),
            'xero' => $this->xeroService->connect($credentials),
            default => throw new AccountingIntegrationException('Unsupported accounting platform'),
        };
    }
}
