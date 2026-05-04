<?php

declare(strict_types=1);

namespace App\Tests\Service\GestionUser;

use App\Service\GestionUser\ValidationService;
use PHPUnit\Framework\TestCase;

final class ValidationServiceTest extends TestCase
{
    public function testValidateReturnsNormalizedDataWhenInputIsValid(): void
    {
        $service = new ValidationService();

        $result = $service->validate([
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'JANE.DOE@MAIL.COM',
            'age' => '27',
            'role' => 'admin',
            'password' => 'Password1',
        ], true, true);

        self::assertSame([], $result['errors']);
        self::assertSame('jane.doe@mail.com', $result['data']['email']);
        self::assertSame(27, $result['data']['age']);
        self::assertSame('ADMIN', $result['data']['role']);
    }

    public function testValidateReturnsFieldErrorsForInvalidPasswordAndRole(): void
    {
        $service = new ValidationService();

        $result = $service->validate([
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'jane@mail.com',
            'age' => '27',
            'role' => 'manager',
            'password' => 'abcdefg',
        ], true, true);

        self::assertArrayHasKey('role', $result['fieldErrors']);
        self::assertArrayHasKey('password', $result['fieldErrors']);
        self::assertStringContainsString('Role must be USER or ADMIN', $result['fieldErrors']['role']);
    }
}

