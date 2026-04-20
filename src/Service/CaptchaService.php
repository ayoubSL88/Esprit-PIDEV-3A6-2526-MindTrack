<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class CaptchaService
{
    private const CODE_LENGTH = 6;
    private const CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    private const SESSION_PREFIX = 'captcha_challenge_';
    private const VERIFIED_PREFIX = 'captcha_verified_';

    public function ensureChallenge(SessionInterface $session, string $context): string
    {
        $code = $session->get($this->getSessionKey($context));

        if (!is_string($code) || $code === '') {
            return $this->refreshChallenge($session, $context);
        }

        return $code;
    }

    public function refreshChallenge(SessionInterface $session, string $context): string
    {
        $code = $this->generateCode();
        $session->set($this->getSessionKey($context), $code);
        $session->remove($this->getVerifiedSessionKey($context));

        return $code;
    }

    public function isVerified(SessionInterface $session, string $context): bool
    {
        return $session->get($this->getVerifiedSessionKey($context), false) === true;
    }

    public function markVerified(SessionInterface $session, string $context): void
    {
        $session->set($this->getVerifiedSessionKey($context), true);
    }

    public function clearVerified(SessionInterface $session, string $context): void
    {
        $session->remove($this->getVerifiedSessionKey($context));
    }

    public function verify(SessionInterface $session, string $context, ?string $input): bool
    {
        $code = $session->get($this->getSessionKey($context));
        $submitted = trim((string) $input);

        if (!is_string($code) || $code === '' || $submitted === '') {
            $this->refreshChallenge($session, $context);

            return false;
        }

        if (!hash_equals($code, $submitted)) {
            $this->refreshChallenge($session, $context);

            return false;
        }

        $this->consume($session, $context);
        $this->markVerified($session, $context);

        return true;
    }

    public function renderSvg(SessionInterface $session, string $context): string
    {
        $code = $this->ensureChallenge($session, $context);
        $width = 220;
        $height = 76;

        $parts = [];
        $parts[] = sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-label="CAPTCHA challenge">', $width, $height, $width, $height);
        $parts[] = '<rect width="100%" height="100%" rx="18" fill="#f8fafc"/>';
        $parts[] = '<rect x="1" y="1" width="218" height="74" rx="17" fill="none" stroke="#d7e1f3"/>';

        for ($i = 0; $i < 7; ++$i) {
            $parts[] = sprintf(
                '<path d="M %d %d C %d %d, %d %d, %d %d" fill="none" stroke="%s" stroke-width="%0.1f" opacity="%0.2f"/>',
                random_int(0, 40),
                random_int(8, $height - 8),
                random_int(50, 90),
                random_int(0, $height),
                random_int(130, 170),
                random_int(0, $height),
                random_int(180, $width),
                random_int(8, $height - 8),
                $this->randomMutedColor(),
                random_int(10, 18) / 10,
                random_int(20, 45) / 100
            );
        }

        for ($i = 0; $i < 24; ++$i) {
            $parts[] = sprintf(
                '<circle cx="%d" cy="%d" r="%0.1f" fill="%s" opacity="%0.2f"/>',
                random_int(8, $width - 8),
                random_int(8, $height - 8),
                random_int(10, 22) / 10,
                $this->randomMutedColor(),
                random_int(15, 40) / 100
            );
        }

        $x = 24;
        foreach (str_split($code) as $character) {
            $fontSize = random_int(29, 36);
            $translateY = random_int(45, 58);
            $rotation = random_int(-18, 18);
            $translateX = $x + random_int(-2, 4);

            $parts[] = sprintf(
                '<text x="%d" y="%d" fill="%s" font-size="%d" font-family="Arial, Helvetica, sans-serif" font-weight="700" transform="rotate(%d %d %d)">%s</text>',
                $translateX,
                $translateY,
                $this->randomTextColor(),
                $fontSize,
                $rotation,
                $translateX,
                $translateY,
                htmlspecialchars($character, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );

            $x += 31;
        }

        $parts[] = '</svg>';

        return implode('', $parts);
    }

    public function getImageUrl(string $context): string
    {
        return sprintf('/captcha/%s/image', rawurlencode($context));
    }

    private function consume(SessionInterface $session, string $context): void
    {
        $session->remove($this->getSessionKey($context));
    }

    private function getSessionKey(string $context): string
    {
        return self::SESSION_PREFIX . preg_replace('/[^a-z0-9_]/i', '_', strtolower($context));
    }

    private function getVerifiedSessionKey(string $context): string
    {
        return self::VERIFIED_PREFIX . preg_replace('/[^a-z0-9_]/i', '_', strtolower($context));
    }

    private function generateCode(): string
    {
        $maxIndex = strlen(self::CHARSET) - 1;
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; ++$i) {
            $code .= self::CHARSET[random_int(0, $maxIndex)];
        }

        return $code;
    }

    private function randomMutedColor(): string
    {
        $palette = ['#94a3b8', '#a78bfa', '#7dd3fc', '#f9a8d4', '#86efac', '#fdba74'];

        return $palette[array_rand($palette)];
    }

    private function randomTextColor(): string
    {
        $palette = ['#0f172a', '#1e293b', '#334155', '#0f766e', '#1d4ed8', '#7c2d12'];

        return $palette[array_rand($palette)];
    }
}
