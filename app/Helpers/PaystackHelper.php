<?php

namespace App\Helpers;

use RuntimeException;

class PaystackHelper
{
    private static function secretKey(): string
    {
        $key = $_ENV['PAYSTACK_SECRET_KEY'] ?? getenv('PAYSTACK_SECRET_KEY') ?? '';
        if ($key === '') {
            throw new RuntimeException('PAYSTACK_SECRET_KEY is not configured.');
        }

        return $key;
    }

    private static function baseUrl(): string
    {
        return $_ENV['PAYSTACK_BASE_URL'] ?? getenv('PAYSTACK_BASE_URL') ?? 'https://api.paystack.co';
    }

    /**
     * Create a temporary bank-transfer account for a deposit.
     */
    public static function createDepositAccount(array $data): array
    {
        $email = $data['email'];
        $amount = $data['amount'];
        $reference = $data['reference'];
        $expiresAt = $data['expiresAt'] ?? null;
        
        $payload = [
            'email' => $email,
            'amount' => self::toMinorUnit($amount),
            'bank_transfer' => ['account_expires_at' => $expiresAt ?? date('Y-m-d H:i:s', strtotime('+30 minutes'))],
            'metadata' => ['reference' => $reference],
        ];

        return self::request('POST', '/charge', $payload);
    }

    /**
     * Create a Paystack transfer recipient.
     */
    public static function createRecipient(string $name, string $accountNumber, string $bankCode, ?int $userId = null, ?string $currency = 'NGN'): array
    {
        $payload = [
            'type' => 'nuban',
            'name' => $name,
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
            'currency' => strtoupper($currency),
        ];

        if ($userId !== null) {
            $payload['metadata'] = ['user_id' => $userId];
        }

        return self::request('POST', '/transferrecipient', $payload);
    }

    /**
     * Withdraw money to a bank account.
     */
    public static function withdraw(string $name, string $accountNumber, string $bankCode, string $amount, string $reference, ?int $userId = null, string $reason = 'Wallet Withdrawal'): array
    {
        $recipient = self::createRecipient($name, $accountNumber, $bankCode, $userId, 'NGN');
        $recipientCode = $recipient['data']['recipient_code'] ?? null;

        if (!$recipientCode) {
            throw new RuntimeException('Paystack did not return a recipient code.');
        }

        return self::request('POST', '/transfer', [
            'source' => 'balance',
            'amount' => self::toMinorUnit($amount),
            'recipient' => $recipientCode,
            'reference' => $reference,
            'reason' => $reason,
        ]);
    }

    /**
     * Send request to Paystack.
     */
    private static function request(string $method, string $endpoint, ?array $payload = null): array
    {
        $method = strtoupper($method);
        $url = rtrim(self::baseUrl(), '/') . '/' . ltrim($endpoint, '/');
        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . self::secretKey(),
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if ($payload !== null && $method !== 'GET') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
        }

        $body = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($body === false) {
            throw new RuntimeException('Paystack request failed: ' . $error);
        }

        $response = json_decode($body, true);

        if (!is_array($response)) {
            throw new RuntimeException('Invalid response from Paystack.');
        }

        if ($httpCode < 200 || $httpCode >= 300 || ($response['status'] ?? false) !== true) {
            throw new RuntimeException($response['message'] ?? 'Paystack request failed.');
        }

        return $response;
    }

    /**
     * Convert NGN amount to kobo.
     *
     * Example: 1000.00 -> 100000
     */
    private static function toMinorUnit(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}