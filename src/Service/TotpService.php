<?php

namespace App\Service;

final class TotpService
{
    private const CODE_LENGTH = 6;
    private const TIME_STEP = 30;
    private const WINDOW = 1;
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(): string
    {
        return $this->encodeBase32(random_bytes(20));
    }

    public function verifyCode(string $secret, string $code): bool
    {
        $normalizedCode = preg_replace('/\s+/', '', trim($code)) ?? '';
        if (strlen($normalizedCode) !== self::CODE_LENGTH || !ctype_digit($normalizedCode)) {
            return false;
        }

        $timeIndex = intdiv(time(), self::TIME_STEP);

        for ($i = -self::WINDOW; $i <= self::WINDOW; ++$i) {
            if (hash_equals($this->generateCodeForTime($secret, $timeIndex + $i), $normalizedCode)) {
                return true;
            }
        }

        return false;
    }

    public function generateOtpAuthUrl(string $email, string $secret, string $issuer = 'MindTrack'): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer),
        );
    }

    public function getQrCodeUrl(string $otpAuthUrl): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . rawurlencode($otpAuthUrl);
    }

    public function getSecondsRemaining(): int
    {
        return self::TIME_STEP - (time() % self::TIME_STEP);
    }

    private function generateCodeForTime(string $secret, int $timeIndex): string
    {
        try {
            $decodedSecret = $this->decodeBase32($secret);
            $message = pack('N*', 0, $timeIndex);
            $hash = hash_hmac('sha1', $message, $decodedSecret, true);

            $offset = ord($hash[strlen($hash) - 1]) & 0x0f;
            $code = (
                ((ord($hash[$offset]) & 0x7f) << 24) |
                ((ord($hash[$offset + 1]) & 0xff) << 16) |
                ((ord($hash[$offset + 2]) & 0xff) << 8) |
                (ord($hash[$offset + 3]) & 0xff)
            ) % 1000000;

            return str_pad((string) $code, self::CODE_LENGTH, '0', STR_PAD_LEFT);
        } catch (\Throwable) {
            return '';
        }
    }

    private function encodeBase32(string $bytes): string
    {
        $result = '';
        $buffer = 0;
        $bitsLeft = 0;

        foreach (unpack('C*', $bytes) as $byte) {
            $buffer = ($buffer << 8) | $byte;
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $result .= self::BASE32_ALPHABET[($buffer >> ($bitsLeft - 5)) & 0x1f];
                $bitsLeft -= 5;
            }
        }

        if ($bitsLeft > 0) {
            $result .= self::BASE32_ALPHABET[($buffer << (5 - $bitsLeft)) & 0x1f];
        }

        return $result;
    }

    private function decodeBase32(string $encoded): string
    {
        $normalized = strtoupper(trim($encoded));
        $normalized = preg_replace('/[^A-Z2-7]/', '', $normalized) ?? '';

        $buffer = 0;
        $bitsLeft = 0;
        $result = '';

        foreach (str_split($normalized) as $char) {
            $value = strpos(self::BASE32_ALPHABET, $char);
            if ($value === false) {
                throw new \InvalidArgumentException('Invalid Base32 secret.');
            }

            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $result .= chr(($buffer >> ($bitsLeft - 8)) & 0xff);
                $bitsLeft -= 8;
            }
        }

        return $result;
    }
}
