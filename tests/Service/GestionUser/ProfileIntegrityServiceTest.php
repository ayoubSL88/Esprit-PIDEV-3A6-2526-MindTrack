<?php

declare(strict_types=1);

namespace App\Tests\Service\GestionUser;

use App\Entity\Utilisateur;
use App\Service\GestionUser\ProfileIntegrityService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class ProfileIntegrityServiceTest extends TestCase
{
    public function testBuildForUserReturnsHighScoreForStrongProfile(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAssociative')->willReturn([
            'requests_30d' => 1,
            'failed_attempts_30d' => 0,
            'locked_tokens_30d' => 0,
        ]);
        $connection->method('fetchOne')->willReturn(2);

        $service = new ProfileIntegrityService($connection);
        $user = $this->makeUser();
        $user->setFace_enabled(true);
        $user->setTotp_enabled(true);
        $user->setTotp_secret('secret');

        $result = $service->buildForUser($user);

        self::assertGreaterThanOrEqual(70, $result['score']);
        self::assertTrue($result['verified_email']['verified']);
        self::assertTrue($result['two_factor']['enabled']);
    }

    public function testBuildForUserAddsHighPriorityActionsForWeakSecurity(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAssociative')->willReturn([
            'requests_30d' => 6,
            'failed_attempts_30d' => 5,
            'locked_tokens_30d' => 2,
        ]);
        $connection->method('fetchOne')->willReturn(0);

        $service = new ProfileIntegrityService($connection);
        $user = $this->makeUser();
        $user->setFace_enabled(false);
        $user->setTotp_enabled(false);
        $user->setTotp_secret('');

        $result = $service->buildForUser($user);
        $codes = array_column($result['actions'], 'code');

        self::assertSame('high', $result['security_hygiene']['risk_level']);
        self::assertContains('enable_2fa', $codes);
        self::assertContains('verify_email_signal', $codes);
    }

    private function makeUser(): Utilisateur
    {
        $user = new Utilisateur();
        $user->setIdU(1);
        $user->setNomU('Doe');
        $user->setPrenomU('Jane');
        $user->setEmailU('jane@mail.com');
        $user->setAgeU(30);
        $user->setPhoneNumber('1234567890');
        $user->setCity('Paris');
        $user->setCountry('France');
        $user->setTimezone('Europe/Paris');
        $user->setOccupation('Engineer');
        $user->setBiography('This is a complete biography with enough text.');
        $user->setProfile_picture_path('uploads/pic.png');
        $user->setRoleU('USER');
        $user->setMdpsU('hash');
        $user->setFace_subject('x');
        $user->setFace_image_id('y');

        return $user;
    }
}

