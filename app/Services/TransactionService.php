<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Document;
use App\Services\DigitalSignatureService;
use App\Services\BlockchainService;

class TransactionService
{
    protected $digitalSignatureService;
    protected $blockchainService;

    public function __construct(DigitalSignatureService $digitalSignatureService, BlockchainService $blockchainService)
    {
        $this->digitalSignatureService = $digitalSignatureService;
        $this->blockchainService = $blockchainService;
    }

    protected $accountingService;

    public function __construct(DigitalSignatureService $digitalSignatureService, BlockchainService $blockchainService, AccountingService $accountingService)
    {
        $this->digitalSignatureService = $digitalSignatureService;
        $this->blockchainService = $blockchainService;
        $this->accountingService = $accountingService;
    }

    public function createTransaction(array $data)
    {
        $transaction = Transaction::create($data);
        $transaction->calculateCommission();

        // Sync invoice with accounting platform
        if ($transaction->accountingIntegration) {
            $this->accountingService->syncInvoice($transaction->accountingIntegration, $transaction);
        }

        return $transaction;
    }

    public function updateTransactionStatus(Transaction $transaction, string $status)
    {
        $transaction->update(['status' => $status]);

        // Sync payment with accounting platform if status is 'paid'
        if ($status === 'paid' && $transaction->accountingIntegration) {
            $this->accountingService->syncPayment($transaction->accountingIntegration, $transaction);
        }

        return $transaction;
    }

    // ... (rest of the methods remain unchanged)
}