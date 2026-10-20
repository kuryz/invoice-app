<?php

namespace App\Helpers;

use RuntimeException;

class NinePsbHelper
{
	public static function validateNotificationHash(array $payload): bool
	{
	    $order = $payload['order'] ?? [];
	    $customerAccount = $payload['customer']['account'] ?? [];
	    $hash = strtoupper(trim($payload['hash'] ?? ''));

	    if (!$hash) return false;

	    $password = $_ENV['9PSB_PASSWORD'] ?? null;

	    if (!$password) {
	        throw new RuntimeException('9PSB credential password is not configured.');
	    }

	    $senderAccountNumber = $customerAccount['senderaccountnumber'] ?? '';
	    $senderBankCode = $customerAccount['senderbankcode'] ?? '';
	    $virtualAccountNumber = $customerAccount['number'] ?? '';
	    $amount = $order['amount'] ?? null;

	    if ($senderAccountNumber === '' || $senderBankCode === '' ||
	        $virtualAccountNumber === '' || $amount === null) {
	        return false;
	    }

	    $formattedAmount = number_format((float) $amount, 2, '.', '');

	    $hashString = $password
	        . $senderAccountNumber
	        . $senderBankCode
	        . $virtualAccountNumber
	        . $formattedAmount;

	    $expectedHash = strtoupper(hash('sha512', $hashString));

	    return hash_equals($expectedHash, $hash);
	}

	public static function confirmPayment(string $sessionId, float $amount, string $accountNumber): array 
	{
	    return apiPost(
	        $_ENV['9PSB_BASE_URL'].'/virtualaccount/confirmpayment',
	        [
	            'sessionid'     => $sessionId,
	            'amount'        => $amount,
	            'accountnumber' => $accountNumber,
	        ]
	    );
	}

	public function createDeposit($value='')
    {
    	
    }    
}