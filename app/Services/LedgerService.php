<?php

namespace App\Services;

use App\Core\Database;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use RuntimeException;

class LedgerService
{
    /**
     * Create a balanced double-entry transaction.
     *
     * Example:
     *
     * debit  = PLATFORM_CASH
     * credit = USER_WALLET
     *
     * Total debit must equal total credit.
     */
    public function createEntry(int $transactionId, array $entries): void 
    {
        if (empty($entries)) {
            throw new RuntimeException(
                'Ledger entries cannot be empty.'
            );
        }

        $totalDebit = '0';
        $totalCredit = '0';

        foreach ($entries as $entry) {
            if (
                !isset(
                    $entry['account_code'],
                    $entry['entry_type'],
                    $entry['amount']
                )
            ) {
                throw new RuntimeException(
                    'Invalid ledger entry.'
                );
            }

            $amount = $this->normalizeAmount(
                $entry['amount']
            );

            if (bccomp($amount, '0', 8) <= 0) {
                throw new RuntimeException(
                    'Ledger amount must be greater than zero.'
                );
            }

            if ($entry['entry_type'] === 'debit') {
                $totalDebit = bcadd(
                    $totalDebit,
                    $amount,
                    8
                );
            } elseif ($entry['entry_type'] === 'credit') {
                $totalCredit = bcadd(
                    $totalCredit,
                    $amount,
                    8
                );
            } else {
                throw new RuntimeException(
                    'Invalid ledger entry type.'
                );
            }
        }

        if (bccomp($totalDebit, $totalCredit,8) !== 0) {
            throw new RuntimeException(
                "Unbalanced ledger. Debit: {$totalDebit}, Credit: {$totalCredit}"
            );
        }

        foreach ($entries as $entry) {
            LedgerEntry::create([
                'transaction_id' => $transactionId,
                'account_code'   => $entry['account_code'],
                'entry_type'     => $entry['entry_type'],
                'amount'         => $this->normalizeAmount(
                    $entry['amount']
                ),
                'description'    => $entry['description'] ?? null,
            ]);
        }
    }

    /**
     * Verify an existing transaction is balanced.
     */
    public function isBalanced(int $transactionId): bool 
    {
        $entries = LedgerEntry::forTransaction($transactionId)->get();

        $debit = '0';
        $credit = '0';

        foreach ($entries as $entry) {
            if ($entry->entry_type === 'debit') {
                $debit = bcadd(
                    $debit,
                    $entry->amount,
                    8
                );
            }

            if ($entry->entry_type === 'credit') {
                $credit = bcadd(
                    $credit,
                    $entry->amount,
                    8
                );
            }
        }

        return bccomp(
            $debit,
            $credit,
            8
        ) === 0;
    }

    private function normalizeAmount(
        $amount
    ): string {
        $amount = (string) $amount;

        if (!is_numeric($amount)) {
            throw new RuntimeException(
                'Invalid monetary amount.'
            );
        }

        if (bccomp($amount, '0', 8) < 0) {
            throw new RuntimeException(
                'Amount cannot be negative.'
            );
        }

        return number_format(
            (float) $amount,
            8,
            '.',
            ''
        );
    }
}