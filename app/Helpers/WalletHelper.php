<?php

namespace App\Helpers;

use App\Models\Wallet;
use App\Models\GeneralSetting;
use RuntimeException;

use App\Helpers\PaystackHelper;

class WalletHelper
{
    /**
     * Initialize a deposit using the payment provider assigned to the wallet.
     */
    public static function deposit(Wallet $wallet, array $data): array
    {
        $provider = self::paymentMethod($wallet);

        return match ($provider) {
            'paystack' => PaystackHelper::createDepositAccount($data),
            '9psb'     => NinePsbHelper::createDeposit($data),

            'manual' => [
                'provider' => 'manual',
                'status' => 'pending',
                'message' => 'Deposit is pending manual processing.',
            ],

            default => [
                'provider' => $provider,
                'status' => 'pending',
                'message' => 'Deposit is pending.',
            ],
        };
    }


    /**
     * Withdraw from wallet.
     */
    public static function withdraw(Wallet $wallet, array $data): array
    {
        $provider = self::paymentMethod($wallet);
        $response = null;
        if ($provider === 'paystack') {
            $response = PaystackHelper::transfer($data);

            // Store $response here if the payment is pending.
            // Example:
            // WalletTransaction::create([...]);
        }
        return $response;

    }

    /**
     * Determine which provider handles this wallet's currency.
     */
    private static function paymentMethod(Wallet $wallet): string
    {
        $providers = GeneralSetting::getDepositProviders();

        return $providers[strtoupper($wallet->currency)] ?? 'manual';
    }
}