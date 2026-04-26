<?php

namespace App\Service;

final class PasswordResetDeliveryService
{
    public function __construct(
        private readonly string $mode,
        private readonly string $fromEmail,
        private readonly string $fromName,
        private readonly string $logFile,
        private readonly string $smtpHost,
        private readonly string $smtpPort,
        private readonly string $smtpEncryption,
        private readonly string $smtpUsername,
        private readonly string $smtpPassword,
    ) {
    }

    public function sendResetCode(string $email, string $code, \DateTimeInterface $expiresAt): void
    {
        $subject = 'MindTrack password reset code';
        $body = sprintf(
            "Your MindTrack password reset code is: %s\n\nThis code expires at %s.\nIf you did not request a password reset, ignore this message.",
            $code,
            $expiresAt->format('Y-m-d H:i:s')
        );

        if ($this->mode === 'smtp') {
            $this->sendViaSmtp($email, $subject, $body);

            return;
        }

        $directory = dirname($this->logFile);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create the password reset log directory.');
        }

        $line = sprintf("[%s] %s => %s\n", date('Y-m-d H:i:s'), $email, $code);
        if (file_put_contents($this->logFile, $line, FILE_APPEND) === false) {
            throw new \RuntimeException('Unable to write the password reset code log.');
        }
    }

    private function sendViaSmtp(string $toEmail, string $subject, string $body): void
    {
        if (
            $this->smtpHost === '' ||
            $this->smtpPort === '' ||
            $this->smtpUsername === '' ||
            $this->smtpPassword === '' ||
            $this->fromEmail === ''
        ) {
            throw new \RuntimeException('SMTP settings are incomplete. Configure host, port, username, password, and from email.');
        }

        $port = (int) $this->smtpPort;
        if ($port <= 0) {
            throw new \RuntimeException('SMTP port is invalid.');
        }

        $encryption = strtolower(trim($this->smtpEncryption));
        $transportHost = $this->smtpHost;
        if ($encryption === 'ssl') {
            $transportHost = 'ssl://' . $transportHost;
        }

        $socket = @stream_socket_client($transportHost . ':' . $port, $errorNumber, $errorMessage, 15);
        if (!is_resource($socket)) {
            throw new \RuntimeException(sprintf('SMTP connection failed: %s', $errorMessage !== '' ? $errorMessage : 'unknown error'));
        }

        stream_set_timeout($socket, 15);

        try {
            $this->expectResponse($socket, [220]);
            $this->writeCommand($socket, 'EHLO localhost');
            $this->expectResponse($socket, [250]);

            if ($encryption === 'tls') {
                $this->writeCommand($socket, 'STARTTLS');
                $this->expectResponse($socket, [220]);

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('SMTP STARTTLS negotiation failed.');
                }

                $this->writeCommand($socket, 'EHLO localhost');
                $this->expectResponse($socket, [250]);
            }

            $this->writeCommand($socket, 'AUTH LOGIN');
            $this->expectResponse($socket, [334]);
            $this->writeCommand($socket, base64_encode($this->smtpUsername));
            $this->expectResponse($socket, [334]);
            $this->writeCommand($socket, base64_encode($this->smtpPassword));
            $this->expectResponse($socket, [235]);

            $this->writeCommand($socket, 'MAIL FROM:<' . $this->fromEmail . '>');
            $this->expectResponse($socket, [250]);
            $this->writeCommand($socket, 'RCPT TO:<' . $toEmail . '>');
            $this->expectResponse($socket, [250, 251]);
            $this->writeCommand($socket, 'DATA');
            $this->expectResponse($socket, [354]);

            $headers = [
                'From: ' . ($this->fromName !== '' ? $this->fromName . ' <' . $this->fromEmail . '>' : $this->fromEmail),
                'To: ' . $toEmail,
                'Subject: ' . $subject,
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
            ];

            $message = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", $body) . "\r\n.";
            $this->writeCommand($socket, $message);
            $this->expectResponse($socket, [250]);

            $this->writeCommand($socket, 'QUIT');
        } finally {
            fclose($socket);
        }
    }

    /**
     * @param resource $socket
     */
    private function writeCommand($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    /**
     * @param resource $socket
     * @param int[] $expectedCodes
     */
    private function expectResponse($socket, array $expectedCodes): void
    {
        $response = '';

        while (($line = fgets($socket, 512)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        if ($response === '') {
            throw new \RuntimeException('SMTP server did not return a response.');
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new \RuntimeException('SMTP error: ' . trim($response));
        }
    }
}
