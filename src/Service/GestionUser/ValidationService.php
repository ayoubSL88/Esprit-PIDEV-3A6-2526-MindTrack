<?php

namespace App\Service\GestionUser;

final class ValidationService
{
    private const NAME_PATTERN = "/^[\p{L}][\p{L}\p{M}\s'\-]{1,79}$/u";

    /**
     * @param array<string, mixed> $rawInput
     * @return array{data: array<string, mixed>, errors: string[], fieldErrors: array<string, string>}
     */
    public function validate(array $rawInput, bool $requirePassword, bool $allowRole): array
    {
        $data = [
            'nom' => trim((string) ($rawInput['nom'] ?? '')),
            'prenom' => trim((string) ($rawInput['prenom'] ?? '')),
            'email' => strtolower(trim((string) ($rawInput['email'] ?? ''))),
            'age' => trim((string) ($rawInput['age'] ?? '')),
            'role' => strtoupper(trim((string) ($rawInput['role'] ?? 'USER'))),
            'password' => (string) ($rawInput['password'] ?? ''),
        ];

        $fieldErrors = [];

        if ($data['nom'] === '') {
            $fieldErrors['nom'] = 'Last name is required.';
        } elseif (!preg_match(self::NAME_PATTERN, $data['nom'])) {
            $fieldErrors['nom'] = 'Last name format is invalid.';
        }

        if ($data['prenom'] === '') {
            $fieldErrors['prenom'] = 'First name is required.';
        } elseif (!preg_match(self::NAME_PATTERN, $data['prenom'])) {
            $fieldErrors['prenom'] = 'First name format is invalid.';
        }

        if ($data['email'] === '') {
            $fieldErrors['email'] = 'Email is required.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $fieldErrors['email'] = 'Please provide a valid email address.';
        }

        $age = filter_var($data['age'], FILTER_VALIDATE_INT);
        if ($data['age'] === '') {
            $fieldErrors['age'] = 'Age is required.';
        } elseif ($age === false || $age < 10 || $age > 120) {
            $fieldErrors['age'] = 'Please provide a valid age between 10 and 120.';
        }

        if ($allowRole && $data['role'] !== 'USER' && $data['role'] !== 'ADMIN') {
            $fieldErrors['role'] = 'Role must be USER or ADMIN.';
        }

        if ($requirePassword) {
            if ($data['password'] === '') {
                $fieldErrors['password'] = 'Password is required.';
            } elseif (mb_strlen($data['password']) < 7) {
                $fieldErrors['password'] = 'Password must contain at least 7 characters.';
            } elseif (!preg_match('/[A-Z]/', $data['password'])) {
                $fieldErrors['password'] = 'Password must contain at least one uppercase letter.';
            } elseif (!preg_match('/[a-z]/', $data['password'])) {
                $fieldErrors['password'] = 'Password must contain at least one lowercase letter.';
            } elseif (!preg_match('/\d/', $data['password'])) {
                $fieldErrors['password'] = 'Password must contain at least one number.';
            }
        }

        $data['age'] = is_int($age) ? $age : null;
        $errors = array_values(array_unique(array_values($fieldErrors)));

        return [
            'data' => $data,
            'errors' => $errors,
            'fieldErrors' => $fieldErrors,
        ];
    }
}