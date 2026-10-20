<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\IdempotencyKey;
use RuntimeException;

class TransactionService
{

    /**
     * get a pending transaction.
     */
    public function get($referenceId)
    {
        return Transaction::where('reference_id', $referenceId)->first();
    }
    /**
     * Create a pending transaction.
     */
    public function create(
        int $walletId,
        string $type,
        string $amount,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $idempotencyKey = null,
        ?array $metadata = null
    ): Transaction {
        if (bccomp($amount, '0', 8) <= 0) {
            throw new RuntimeException(
                'Transaction amount must be greater than zero.'
            );
        }

        if ($idempotencyKey) {
            $existing =
                Transaction::findByIdempotencyKey(
                    $idempotencyKey
                );

            if ($existing) {
                return $existing;
            }
        }

        return Transaction::create([
            'uuid'           => generateUuid(),
            'wallet_id'      => $walletId,
            'type'           => $type,
            'amount'         => $amount,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'status'         => 'pending',
            'metadata'       => $metadata,
            'idempotency_key'=> $idempotencyKey,
        ]);
    }

    public function complete(Transaction $transaction): void 
    {
        Transaction::where('id', $transaction->id)->update([
            'status' => 'completed',
            'sd_status' => 'completed',
        ]);
    }

    public function fail(Transaction $transaction): void 
    {
        Transaction::where('id', $transaction->id)->update([
            'sd_status' => 'failed',
        ]);
    }

    public function cancel(
        Transaction $transaction
    ): void {
        Transaction::where('id', $transaction->id)->update([
            'status' => 'cancelled',
        ]);
    }
}