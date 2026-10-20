<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

final class InvoiceMagicLinkMailer
{
    public static function send(
        string $to,
        string $name,
        string $url
    ): bool {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log('NairaBill mailer: Invalid recipient address.');
            return false;
        }

        $host = trim((string) ($_ENV['MAIL_HOST'] ?? ''));
        $username = trim((string) ($_ENV['MAIL_USERNAME'] ?? ''));
        $password = (string) ($_ENV['MAIL_PASSWORD'] ?? '');
        $port = (int) ($_ENV['MAIL_PORT'] ?? 587);

        $encryption = strtolower(trim(
            (string) ($_ENV['MAIL_ENCRYPTION'] ?? 'tls')
        ));

        $from = trim((string) (
            $_ENV['MAIL_FROM_ADDRESS'] ?? $username
        ));

        $fromName = trim((string) (
            $_ENV['MAIL_FROM_NAME'] ?? 'NairaBill'
        ));

        // Validate SMTP configuration.
        if (
            $host === '' ||
            $username === '' ||
            $password === '' ||
            !filter_var($from, FILTER_VALIDATE_EMAIL) ||
            !in_array($encryption, ['tls', 'ssl'], true) ||
            $port < 1 ||
            $port > 65535
        ) {
            error_log('NairaBill mailer: Invalid SMTP configuration.');
            return false;
        }

        // Magic links should use HTTPS.
        $urlParts = parse_url($url);

        if (
            !is_array($urlParts) ||
            strtolower((string) ($urlParts['scheme'] ?? '')) !== 'https' ||
            empty($urlParts['host'])
        ) {
            error_log('NairaBill mailer: Invalid sign-in URL.');
            return false;
        }

        $safeName = htmlspecialchars(
            $name,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $safeUrl = htmlspecialchars(
            $url,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $subject = 'Your secure NairaBill sign-in link';

        $html = '<!doctype html>
<html>
<body style="margin:0;background:#f3f6fb;font-family:Arial,sans-serif;color:#172033">
<div style="max-width:560px;margin:32px auto;background:#fff;border:1px solid #e4e9f2;border-radius:18px;overflow:hidden">
    <div style="background:#101b31;padding:25px 30px;color:#fff;font-size:22px;font-weight:bold">
        Naira<span style="color:#78f0bd">Bill</span>
    </div>
    <div style="padding:30px">
        <p>Hello ' . $safeName . ',</p>
        <h1 style="font-size:25px;line-height:1.25">Your secure sign-in link</h1>
        <p style="color:#5d687b;line-height:1.7">
            Use the button below to sign in to your merchant workspace.
            This link expires in 15 minutes and can only be used once.
        </p>
        <p style="margin:28px 0">
            <a href="' . $safeUrl . '" style="background:#087f65;color:white;text-decoration:none;padding:14px 20px;border-radius:10px;font-weight:bold;display:inline-block">
                Sign in to NairaBill
            </a>
        </p>
        <p style="font-size:12px;color:#7c8798;line-height:1.6">
            If you did not request this email, you can safely ignore it.
            For security, do not forward this link.
        </p>
    </div>
</div>
</body>
</html>';

        $text = "Hello {$name},\n\n"
            . "Sign in using this secure link "
            . "(expires in 15 minutes):\n"
            . "{$url}\n\n"
            . "If you did not request this email, ignore it.\n";

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->Port = $port;
            $mail->CharSet = 'UTF-8';

            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            // Never disable TLS certificate verification.
            $mail->SMTPDebug = 0;
            $mail->Timeout = 15;

            $mail->setFrom($from, $fromName);
            $mail->addAddress($to, $name);
            $mail->addReplyTo($from, $fromName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = $text;

            $mail->send();

            return true;
        } catch (Exception $e) {
            // Do not log the sign-in URL or SMTP credentials.
            error_log(
                'NairaBill mailer: SMTP send failed. ' .
                $mail->ErrorInfo
            );

            return false;
        }
    }
}