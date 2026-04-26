<?php

namespace App\Service;

use App\Entity\Password_reset_tokens;
use App\Entity\Utilisateur;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordResetService
{
    public const STATUS_OK = 'OK';
    public const STATUS_INVALID = 'INVALID';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_TOO_MANY_ATTEMPTS = 'TOO_MANY_ATTEMPTS';
    public const STATUS_NOT_FOUND = 'NOT_FOUND';

    public const REQUEST_SENT = 'SENT';
    public const REQUEST_RATE_LIMITED = 'RATE_LIMITED';
    public const REQUEST_SILENT = 'SILENT';

    private const CODE_LENGTH = 6;
    private const EXPIRES_IN_SECONDS = 600;
    private const MAX_ATTEMPTS = 5;
    private const REQUEST_COOLDOWN_SECONDS = 60;
    private const MAX_REQUESTS_PER_HOUR = 5;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PasswordResetDeliveryService $deliveryService,
    ) {
    }

    public function requestReset(string $email): string
    {
        /** @var Utilisateur|null $user */
        $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['emailU' => strtolower(trim($email))]);
        if (!$user instanceof Utilisateur) {
            return self::REQUEST_SILENT;
        }

        $now = new \DateTime();

        $latestRequest = $this->connection->fetchAssociative(
            'SELECT created_at FROM password_reset_tokens WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1',
            ['user_id' => $user->getIdU()],
        );

        if (is_array($latestRequest) && isset($latestRequest['created_at'])) {
            $latestCreatedAt = new \DateTime((string) $latestRequest['created_at']);
            if (($now->getTimestamp() - $latestCreatedAt->getTimestamp()) < self::REQUEST_COOLDOWN_SECONDS) {
                return self::REQUEST_RATE_LIMITED;
            }
        }

        $hourlyCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM password_reset_tokens WHERE user_id = :user_id AND created_at >= :cutoff',
            [
                'user_id' => $user->getIdU(),
                'cutoff' => (clone $now)->modify('-1 hour')->format('Y-m-d H:i:s'),
            ],
        );

        if ($hourlyCount >= self::MAX_REQUESTS_PER_HOUR) {
            return self::REQUEST_RATE_LIMITED;
        }

        $code = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
        $codeHash = password_hash($code, PASSWORD_DEFAULT);
        if ($codeHash === false) {
            throw new \RuntimeException('Unable to hash the password reset code.');
        }

        $nextTokenId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id), 0) + 1 FROM password_reset_tokens');

        $this->connection->beginTransaction();

        try {
            $token = new Password_reset_tokens();
            $token->setId($nextTokenId);
            $token->setUser_id($user);
            $token->setCode_hash($codeHash);
            $token->setExpires_at((clone $now)->modify('+' . self::EXPIRES_IN_SECONDS . ' seconds'));
            $token->setUsed(false);
            $token->setAttempts(0);
            $token->setCreated_at($now);

            $this->entityManager->persist($token);
            $this->entityManager->flush();

            $this->deliveryService->sendResetCode($user->getEmailU(), $code, $token->getExpires_at());
            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();
            $this->entityManager->clear();
            throw $throwable;
        }

        return self::REQUEST_SENT;
    }

    public function verifyCode(string $email, string $code): string
    {
        $token = $this->findLatestActiveToken($email);
        if ($token === null) {
            return self::STATUS_NOT_FOUND;
        }

        return $this->checkToken($token, $code, true);
    }

    public function resetPassword(string $email, string $code, string $newPassword): string
    {
        /** @var Utilisateur|null $user */
        $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['emailU' => strtolower(trim($email))]);
        if (!$user instanceof Utilisateur) {
            return self::STATUS_NOT_FOUND;
        }

        $token = $this->findLatestActiveToken($email);
        if ($token === null) {
            return self::STATUS_NOT_FOUND;
        }

        $status = $this->checkToken($token, $code, false);
        if ($status !== self::STATUS_OK) {
            return $status;
        }

        $passwordHash = $this->passwordHasher->hashPassword($user, $newPassword);

        $this->connection->beginTransaction();

        try {
            $user->setMdpsU($passwordHash);
            $token->setUsed(true);
            $this->entityManager->flush();
            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        }

        return self::STATUS_OK;
    }

    private function findLatestActiveToken(string $email): ?Password_reset_tokens
    {
        /** @var Utilisateur|null $user */
        $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['emailU' => strtolower(trim($email))]);
        if (!$user instanceof Utilisateur) {
            return null;
        }

        /** @var Password_reset_tokens|null $token */
        $token = $this->entityManager->getRepository(Password_reset_tokens::class)->findOneBy(
            ['user_id' => $user, 'used' => false],
            ['created_at' => 'DESC'],
        );

        return $token;
    }

    private function checkToken(Password_reset_tokens $token, string $code, bool $persistAttempt): string
    {
        $now = new \DateTime();
        if ($token->getExpires_at() < $now) {
            $token->setUsed(true);
            $this->entityManager->flush();

            return self::STATUS_EXPIRED;
        }

        if ($token->getAttempts() >= self::MAX_ATTEMPTS) {
            $token->setUsed(true);
            $this->entityManager->flush();

            return self::STATUS_TOO_MANY_ATTEMPTS;
        }

        $normalizedCode = preg_replace('/\s+/', '', trim($code)) ?? '';
        if (!password_verify($normalizedCode, $token->getCode_hash())) {
            if ($persistAttempt) {
                $token->setAttempts($token->getAttempts() + 1);
                if ($token->getAttempts() >= self::MAX_ATTEMPTS) {
                    $token->setUsed(true);
                }
                $this->entityManager->flush();
            }

            return $token->getAttempts() >= self::MAX_ATTEMPTS
                ? self::STATUS_TOO_MANY_ATTEMPTS
                : self::STATUS_INVALID;
        }

        return self::STATUS_OK;
    }
}
