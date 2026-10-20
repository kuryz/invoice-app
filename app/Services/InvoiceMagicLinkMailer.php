<?php
namespace App\Services;

/** Sends short-lived, single-use merchant sign-in links using the server's configured MTA. */
final class InvoiceMagicLinkMailer
{
    public static function send(string $to, string $name, string $url): bool
    {
        $from = trim((string)($_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@' . (parse_url((string)($_ENV['APP_URL'] ?? ''), PHP_URL_HOST) ?: 'localhost')));
        $fromName = trim((string)($_ENV['MAIL_FROM_NAME'] ?? 'NairaBill'));
        $subject = 'Your secure NairaBill sign-in link';
        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<!doctype html><html><body style="margin:0;background:#f3f6fb;font-family:Arial,sans-serif;color:#172033"><div style="max-width:560px;margin:32px auto;background:#fff;border:1px solid #e4e9f2;border-radius:18px;overflow:hidden"><div style="background:#101b31;padding:25px 30px;color:#fff;font-size:22px;font-weight:bold">Naira<span style="color:#78f0bd">Bill</span></div><div style="padding:30px"><p>Hello '.$safeName.',</p><h1 style="font-size:25px;line-height:1.25">Your secure sign-in link</h1><p style="color:#5d687b;line-height:1.7">Use the button below to sign in to your merchant workspace. This link expires in 15 minutes and can only be used once.</p><p style="margin:28px 0"><a href="'.$safeUrl.'" style="background:#087f65;color:white;text-decoration:none;padding:14px 20px;border-radius:10px;font-weight:bold;display:inline-block">Sign in to NairaBill</a></p><p style="font-size:12px;color:#7c8798;line-height:1.6">If you did not request this email, you can safely ignore it. For security, do not forward this link.</p></div></div></body></html>';
        $boundary = 'nb_' . bin2hex(random_bytes(12));
        $headers = [
            'MIME-Version: 1.0',
            'From: ' . sprintf('"%s" <%s>', addcslashes($fromName, '"\\'), $from),
            'Reply-To: ' . $from,
            'Content-Type: multipart/alternative; boundary="'.$boundary.'"',
            'X-Mailer: NairaBill'
        ];
        $text = "Hello {$name},\n\nSign in using this secure link (expires in 15 minutes):\n{$url}\n\nIf you did not request this email, ignore it.\n";
        $body = '--'.$boundary."\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n".$text."\r\n";
        $body .= '--'.$boundary."\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n".$html."\r\n--".$boundary."--\r\n";
        return @mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $body, implode("\r\n", $headers));
    }
}
