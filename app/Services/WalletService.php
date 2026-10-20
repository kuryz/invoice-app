<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Wallet;
use App\Models\Transaction;
use RuntimeException;

class WalletService
{
    private TransactionService $transactions;
    private LedgerService $ledger;

    public function __construct()
    {
        $this->transactions = new TransactionService;
        $this->ledger = new LedgerService;
    }

    public function create(int $userId, string $currency = 'NGN'): Wallet {
        $currency = strtoupper(trim($currency));

        /*
         * Prevent unsupported currencies.
         */
        $allowedCurrencies = [
            'NGN',
            'USD',
            'GHS',
            'KES',
        ];

        if (!in_array($currency, $allowedCurrencies, true)) {
            throw new RuntimeException('Unsupported wallet currency.');
        }

        /*
         * Because the database has:
         *
         * UNIQUE(user_id, currency)
         *
         * a user can only have one wallet
         * for each currency.
         */
        $existing = Wallet::where('user_id', $userId)->where('currency', $currency)->first();

        if ($existing) {
            return $existing;
        }

        $wallet = new Wallet();

        $wallet->fill([
            'uuid' => generateUuid(),
            'user_id' => $userId,
            'currency' => $currency,
            'balance' => '0.00000000',
            'available_balance' => '0.00000000',
            'locked_balance' => '0.00000000',
            'status' => 'active',
            'version' => 0,
        ]);

        $wallet->save();
        return $wallet;
    }

    public function credit(
        int $walletId,
        string $amount,
        string $referenceType,
        string $referenceId,
        ?string $idempotencyKey = null,
        ?array $metadata = null
    ): Transaction {
        return Database::transaction(
            fn () => $this->creditInsideTransaction(
                $walletId,
                $amount,
                $referenceType,
                $referenceId,
                $idempotencyKey,
                $metadata
            )
        );
    }

    /**
     * Credit a wallet.
     */
    public function creditInsideTransaction(
        int $walletId,
        string $amount,
        string $referenceType,
        string $referenceId,
        ?string $idempotencyKey = null,
        ?array $metadata = null
    ): Transaction {
        $this->validateAmount($amount);

        $wallet = Wallet::lock($walletId);

        if (!$wallet) {
            throw new RuntimeException('Wallet not found.');
        }

        if (!$wallet->isActive()) {
            throw new RuntimeException('Wallet is not active.');
        }

        //already created
        $transaction = $this->transactions->get($referenceId);
        // $transaction = $this->transactions->create(
        //     $walletId,
        //     'credit',
        //     $amount,
        //     $referenceType,
        //     $referenceId,
        //     $idempotencyKey,
        //     $metadata
        // );

        // $wallet->update([
        //     'balance' => bcadd($wallet->balance, $amount, 8),
        //     'available_balance' => bcadd($wallet->available_balance, $amount, 8),
        //     'version' => $wallet->version + 1,
        // ]);
        

        $this->transactions->complete($transaction);

        Wallet::where('id', $walletId)->update([
            'balance' => bcadd($wallet->balance, $amount, 8),
            'available_balance' => bcadd($wallet->available_balance, $amount, 8),
            'version' => $wallet->version + 1,
        ]);

        /*
         * DR PLATFORM_CASH
         * CR USER_WALLET
         */
        $this->ledger->createEntry($transaction->id, [
            [
                'account_code' => 'PLATFORM_CASH',
                'entry_type' => 'debit',
                'amount' => $amount,
                'description' => 'Wallet credit',
            ],
            [
                'account_code' => 'USER_WALLET_' . $walletId,
                'entry_type' => 'credit',
                'amount' => $amount,
                'description' => 'Customer wallet credit',
            ],
        ]);


        return $transaction;
    }

    /**
     * Debit wallet.
     *
     * Standalone operation.
     */
    public function debit(
        int $walletId,
        string $amount,
        string $referenceType,
        string $referenceId,
        ?string $idempotencyKey = null,
        ?array $metadata = null
    ): Transaction {
        return Database::transaction(
            fn () => $this->debitInsideTransaction(
                $walletId,
                $amount,
                $referenceType,
                $referenceId,
                $idempotencyKey,
                $metadata
            )
        );
    }

    /**
     * Debit available wallet balance.
     */
    public function debitInsideTransaction(
        int $walletId,
        string $amount,
        string $referenceType,
        string $referenceId,
        ?string $idempotencyKey = null,
        ?array $metadata = null
    ): Transaction {
        $this->validateAmount($amount);

        $wallet = Wallet::lock($walletId);

        if (!$wallet) {
            throw new RuntimeException('Wallet not found.');
        }

        if (!$wallet->isActive()) {
            throw new RuntimeException('Wallet is not active.');
        }

        if (bccomp($wallet->available_balance, $amount, 8) < 0) {
            throw new RuntimeException('Insufficient available balance.');
        }

        $transaction = $this->transactions->create(
            $walletId,
            'debit',
            $amount,
            $referenceType,
            $referenceId,
            $idempotencyKey,
            $metadata
        );

        $wallet->update([
            'balance' => bcsub($wallet->balance, $amount, 8),
            'available_balance' => bcsub($wallet->available_balance, $amount, 8),
            'version' => $wallet->version + 1,
        ]);

        /*
         * DR USER_WALLET
         * CR PLATFORM_CASH
         */
        $this->ledger->createEntry($transaction->id, [
            [
                'account_code' => 'USER_WALLET_' . $walletId,
                'entry_type' => 'debit',
                'amount' => $amount,
                'description' => 'Wallet debit',
            ],
            [
                'account_code' => 'PLATFORM_CASH',
                'entry_type' => 'credit',
                'amount' => $amount,
                'description' => 'Wallet debit settlement',
            ],
        ]);

        $this->transactions->complete($transaction);

        return $transaction;
    }

    /**
     * Lock wallet funds for escrow.
     *
     * Standalone operation.
     *
     * This method starts and commits the database transaction.
     */
    public function lock(
        int $walletId,
        string $amount,
        string $escrowId,
        ?string $idempotencyKey = null
    ): Transaction {
        return Database::transaction(
            fn () => $this->lockInsideTransaction(
                $walletId,
                $amount,
                $escrowId,
                $idempotencyKey
            )
        );
    }

    /**
     * Lock available wallet funds for escrow.
     */
    public function lockInsideTransaction(
        int $walletId,
        string $amount,
        string $escrowId,
        ?string $idempotencyKey = null
    ): Transaction {
        $this->validateAmount($amount);

        $wallet = Wallet::lock($walletId);

        if (!$wallet) {
            throw new RuntimeException('Wallet not found.');
        }

        if (!$wallet->isActive()) {
            throw new RuntimeException('Wallet is not active.');
        }

        if (bccomp($wallet->available_balance, $amount, 8) < 0) {
            throw new RuntimeException('Insufficient available balance.');
        }

        $transaction = $this->transactions->create(
            $walletId,
            'escrow_lock',
            $amount,
            'escrow',
            $escrowId,
            $idempotencyKey
        );

        $wallet->update([
            'available_balance' => bcsub($wallet->available_balance, $amount, 8),
            'locked_balance' => bcadd($wallet->locked_balance, $amount, 8),
            'version' => $wallet->version + 1,
        ]);

        /*
         * Locking does NOT change total wallet balance.
         *
         * AVAILABLE -> ESCROW LOCKED
         */
        $this->ledger->createEntry($transaction->id, [
            [
                'account_code' => 'USER_WALLET_' . $walletId,
                'entry_type' => 'debit',
                'amount' => $amount,
                'description' => 'Move funds to escrow',
            ],
            [
                'account_code' => 'ESCROW_LIABILITY',
                'entry_type' => 'credit',
                'amount' => $amount,
                'description' => 'Escrow funds locked',
            ],
        ]);

        $this->transactions->complete($transaction);

        return $transaction;
    }

    public function unlock(
        int $walletId,
        string $amount,
        string $escrowId,
        ?string $idempotencyKey = null
    ): Transaction {
        return Database::transaction(
            fn () => $this->unlockInsideTransaction(
                $walletId,
                $amount,
                $escrowId,
                $idempotencyKey
            )
        );
    }

    /**
     * Release locked funds back to the user's
     * available wallet balance.
     *
     * Used for a cancelled escrow/refund.
     */
    public function unlockInsideTransaction(
        int $walletId,
        string $amount,
        string $escrowId,
        ?string $idempotencyKey = null
    ): Transaction {
        $this->validateAmount($amount);

        $wallet = Wallet::lock($walletId);

        if (!$wallet) {
            throw new RuntimeException('Wallet not found.');
        }

        if (bccomp($wallet->locked_balance, $amount, 8) < 0) {
            throw new RuntimeException('Insufficient locked balance.');
        }

        $transaction = $this->transactions->create(
            $walletId,
            'escrow_refund',
            $amount,
            'escrow',
            $escrowId,
            $idempotencyKey
        );

        $wallet->update([
            'available_balance' => bcadd($wallet->available_balance, $amount, 8),
            'locked_balance' => bcsub($wallet->locked_balance, $amount, 8),
            'version' => $wallet->version + 1,
        ]);

        /*
         * DR ESCROW
         * CR USER_WALLET
         */
        $this->ledger->createEntry($transaction->id, [
            [
                'account_code' => 'ESCROW_LIABILITY',
                'entry_type' => 'debit',
                'amount' => $amount,
                'description' => 'Refund escrow',
            ],
            [
                'account_code' => 'USER_WALLET_' . $walletId,
                'entry_type' => 'credit',
                'amount' => $amount,
                'description' => 'Return escrow funds',
            ],
        ]);

        $this->transactions->complete($transaction);

        return $transaction;
    }

    private function validateAmount(string $amount): void
    {
        if (!is_numeric($amount)) {
            throw new RuntimeException('Invalid amount.');
        }

        if (bccomp($amount, '0', 8) <= 0) {
            throw new RuntimeException('Amount must be greater than zero.');
        }
    }
}