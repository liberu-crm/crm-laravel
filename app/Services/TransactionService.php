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

    public function createTransaction(array $data)
    {
        $transaction = Transaction::create($data);
        $transaction->calculateCommission();
        return $transaction;
    }

    public function updateTransactionStatus(Transaction $transaction, string $status)
    {
        $transaction->update(['status' => $status]);
        return $transaction;
    }

    public function generateContractualDocument(Transaction $transaction)
    {
        $documentTemplate = DocumentTemplate::where('type', 'sale_agreement')->first();
        $content = $documentTemplate->renderContent([
            'buyer' => $transaction->buyer->name,
            'seller' => $transaction->seller->name,
            'property' => $transaction->property->title,
            'amount' => $transaction->transaction_amount,
            'date' => $transaction->transaction_date->format('Y-m-d'),
        ]);

        $document = new Document([
            'title' => 'Sale Agreement - ' . $transaction->property->title,
            'content' => $content,
            'transaction_id' => $transaction->id,
        ]);
        $document->save();

        return $document;
    }

    public function signDocument(Document $document, $user, $signatureData)
    {
        $signature = $this->digitalSignatureService->signDocument($user, $document, $signatureData);
        
        // Record the signature on the blockchain
        $this->blockchainService->recordSignature($signature);

        return $signature;
    }
}