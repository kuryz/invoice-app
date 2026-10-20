<?php

namespace App\Services;

use App\Core\Database;
use App\Models\EscrowAgreement;
use App\Models\Wallet;
use RuntimeException;

class EscrowService
{
    private WalletService $wallets;
    private TransactionService $transactions;
    private LedgerService $ledger;

    public function __construct() 
    {
        $this->wallets = new WalletService;
        $this->transactions = new TransactionService;
        $this->ledger = new LedgerService;
    }

    /**
     * Create an escrow agreement.
     */
    public function create(
        int $buyerId,
        int $sellerId,
        string $amount,
        string $currency = 'USD',
        string $feeAmount = '0',
        ?array $releaseConditions = null,
        ?string $expiresAt = null,
        ?array $metadata = null
    ): EscrowAgreement {
        if ($buyerId === $sellerId) {
            throw new RuntimeException(
                'Buyer and seller cannot be the same user.'
            );
        }

        if (bccomp($amount, '0', 8) <= 0) {
            throw new RuntimeException(
                'Escrow amount must be greater than zero.'
            );
        }

        return EscrowAgreement::create([
            'uuid'              => generateUuid(),
            'buyer_id'          => $buyerId,
            'seller_id'         => $sellerId,
            'amount'            => $amount,
            'currency'          => strtoupper($currency),
            'fee_amount'        => $feeAmount,
            'status'            => 'pending',
            'release_conditions' =>
                $releaseConditions,
            'expires_at'        => $expiresAt,
            'metadata'          => $metadata,
        ]);
    }

    /**
     * Fund an escrow.
     *
     * Buyer available balance becomes locked.
     */
    public function fund(
        string $escrowUuid,
        ?string $idempotencyKey = null
    ): EscrowAgreement {
        return Database::transaction(
            function () use (
                $escrowUuid,
                $idempotencyKey
            ) {
                $escrow =
                    EscrowAgreement::findByUuid(
                        $escrowUuid
                    );

                if (!$escrow) {
                    throw new RuntimeException(
                        'Escrow agreement not found.'
                    );
                }

                if (!$escrow->canBeFunded()) {
                    throw new RuntimeException(
                        'Escrow cannot be funded in its current state.'
                    );
                }

                $wallet = Wallet::activeForUser(
                    $escrow->buyer_id,
                    $escrow->currency
                );

                if (!$wallet) {
                    throw new RuntimeException(
                        'Buyer wallet not found.'
                    );
                }

                /*
                 * Lock the buyer's funds.
                 */
                $this->wallets->lockInsideTransaction(
                    $wallet->id,
                    (string) $escrow->amount,
                    $escrow->uuid,
                    $idempotencyKey
                );

                /*
                 * Escrow is now funded.
                 */
                $escrow->update([
                    'status' => 'active',
                ]);

                return $escrow;
            }
        );
    }

    /**
     * Release escrow to seller.
     */
    public function release(
        string $escrowUuid,
        ?string $idempotencyKey = null
    ): EscrowAgreement {
        return Database::transaction(
            function () use (
                $escrowUuid,
                $idempotencyKey
            ) {
                $escrow =
                    EscrowAgreement::findByUuid(
                        $escrowUuid
                    );

                if (!$escrow) {
                    throw new RuntimeException(
                        'Escrow agreement not found.'
                    );
                }

                if (!$escrow->canBeReleased()) {
                    throw new RuntimeException(
                        'Escrow cannot be released.'
                    );
                }

                /*
                 * Buyer wallet.
                 */
                $buyerWallet =
                    Wallet::lock(
                        Wallet::activeForUser(
                            $escrow->buyer_id,
                            $escrow->currency
                        )->id
                    );

                /*
                 * Seller wallet.
                 */
                $sellerWallet =
                    Wallet::lock(
                        Wallet::activeForUser(
                            $escrow->seller_id,
                            $escrow->currency
                        )->id
                    );

                if (!$buyerWallet) {
                    throw new RuntimeException(
                        'Buyer wallet not found.'
                    );
                }

                if (!$sellerWallet) {
                    throw new RuntimeException(
                        'Seller wallet not found.'
                    );
                }

                $amount = (string) $escrow->amount;

                if (
                    bccomp(
                        $buyerWallet->locked_balance,
                        $amount,
                        8
                    ) < 0
                ) {
                    throw new RuntimeException(
                        'Insufficient locked escrow balance.'
                    );
                }

                /*
                 * Create release transaction against
                 * seller wallet.
                 */
                $transaction =
                    $this->transactions->create(
                        $sellerWallet->id,
                        'escrow_release',
                        $amount,
                        'escrow',
                        $escrow->uuid,
                        $idempotencyKey
                    );

                /*
                 * Buyer:
                 *
                 * locked decreases.
                 * Total balance decreases.
                 */
                $buyerWallet->update([
                    'balance' => bcsub(
                        $buyerWallet->balance,
                        $amount,
                        8
                    ),

                    'locked_balance' => bcsub(
                        $buyerWallet->locked_balance,
                        $amount,
                        8
                    ),

                    'version' => $buyerWallet->version + 1,
                ]);

                /*
                 * Seller:
                 *
                 * available increases.
                 * total balance increases.
                 */
                $sellerWallet->update([
                    'balance' => bcadd(
                        $sellerWallet->balance,
                        $amount,
                        8
                    ),

                    'available_balance' => bcadd(
                        $sellerWallet->available_balance,
                        $amount,
                        8
                    ),

                    'version' => $sellerWallet->version + 1,
                ]);

                /*
                 * Accounting:
                 *
                 * DR Escrow Liability
                 * CR Seller Wallet Liability
                 */
                $this->ledger->createEntry(
                    $transaction->id,
                    [
                        [
                            'account_code' =>
                                'ESCROW_LIABILITY',
                            'entry_type' => 'debit',
                            'amount'     => $amount,
                            'description' =>
                                'Release escrow',
                        ],
                        [
                            'account_code' =>
                                'USER_WALLET_' .
                                $sellerWallet->id,
                            'entry_type' => 'credit',
                            'amount'     => $amount,
                            'description' =>
                                'Escrow payment to seller',
                        ],
                    ]
                );

                $this->transactions->complete(
                    $transaction
                );

                $escrow->update([
                    'status'      => 'released',
                    'released_at' =>
                        date('Y-m-d H:i:s'),
                ]);

                return $escrow;
            }
        );
    }

    /**
     * Refund escrow to buyer.
     */
    public function refund(
        string $escrowUuid,
        ?string $idempotencyKey = null
    ): EscrowAgreement {
        return Database::transaction(
            function () use (
                $escrowUuid,
                $idempotencyKey
            ) {
                $escrow =
                    EscrowAgreement::findByUuid(
                        $escrowUuid
                    );

                if (!$escrow) {
                    throw new RuntimeException(
                        'Escrow agreement not found.'
                    );
                }

                if (!$escrow->canBeRefunded()) {
                    throw new RuntimeException(
                        'Escrow cannot be refunded.'
                    );
                }

                $buyerWallet =
                    Wallet::activeForUser(
                        $escrow->buyer_id,
                        $escrow->currency
                    );

                if (!$buyerWallet) {
                    throw new RuntimeException(
                        'Buyer wallet not found.'
                    );
                }

                /*
                 * Return locked funds.
                 */
                $this->wallets->unlock(
                    $buyerWallet->id,
                    (string) $escrow->amount,
                    $escrow->uuid,
                    $idempotencyKey
                );

                $escrow->update([
                    'status' => 'refunded',
                ]);

                return $escrow;
            }
        );
    }

    /**
     * Mark escrow as disputed.
     */
    public function dispute(
        string $escrowUuid
    ): EscrowAgreement {
        $escrow =
            EscrowAgreement::findByUuid(
                $escrowUuid
            );

        if (!$escrow) {
            throw new RuntimeException(
                'Escrow agreement not found.'
            );
        }

        if (!$escrow->isActive()) {
            throw new RuntimeException(
                'Only active escrows can be disputed.'
            );
        }

        $escrow->update([
            'status' => 'disputed',
        ]);

        return $escrow;
    }

    /**
     * Cancel an unfunded escrow.
     */
    public function cancel(
        string $escrowUuid
    ): EscrowAgreement {
        $escrow =
            EscrowAgreement::findByUuid(
                $escrowUuid
            );

        if (!$escrow) {
            throw new RuntimeException(
                'Escrow agreement not found.'
            );
        }

        if ($escrow->status !== 'pending') {
            throw new RuntimeException(
                'Only pending escrows can be cancelled.'
            );
        }

        $escrow->update([
            'status' => 'cancelled',
        ]);

        return $escrow;
    }
}