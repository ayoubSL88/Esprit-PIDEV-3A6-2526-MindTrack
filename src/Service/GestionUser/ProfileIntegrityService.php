<?php

namespace App\Service\GestionUser;

use App\Entity\Utilisateur;
use Doctrine\DBAL\Connection;

final class ProfileIntegrityService
{
    private const MAX_RESET_ATTEMPTS = 5;

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array{
     *   score:int,
     *   band:string,
     *   window_days:int,
     *   calculated_at:string,
     *   profile_completeness:array{points:int,max:int,percent:int},
     *   verified_email:array{points:int,max:int,verified:bool,note:string},
     *   two_factor:array{points:int,max:int,enabled:bool},
     *   security_hygiene:array{points:int,max:int,face_enabled:bool,suspicious_events:int,risk_level:string},
     *   suspicious_events:list<string>,
     *   actions:list<array{code:string,label:string,priority:string}>
     * }
     */
    public function buildForUser(Utilisateur $user): array
    {
        $securityStats = $this->fetchResetSecurityStats((int) $user->getIdU());

        $profileCompleteness = $this->computeProfileCompleteness($user);
        $verifiedEmail = $this->computeVerifiedEmailSignal((int) $securityStats['verified_email_events']);
        $twoFactor = $this->computeTwoFactor($user);
        $securityHygiene = $this->computeSecurityHygiene($user, $securityStats);

        $score = $profileCompleteness['points'] + $verifiedEmail['points'] + $twoFactor['points'] + $securityHygiene['points'];
        $score = max(0, min(100, $score));

        $suspiciousEvents = $this->buildSuspiciousEvents($securityStats);
        $actions = $this->buildActions($user, $profileCompleteness, $verifiedEmail, $twoFactor, $suspiciousEvents);

        return [
            'score' => $score,
            'band' => $this->scoreBand($score),
            'window_days' => 30,
            'calculated_at' => (new \DateTimeImmutable('now'))->format(\DateTimeInterface::ATOM),
            'profile_completeness' => $profileCompleteness,
            'verified_email' => $verifiedEmail,
            'two_factor' => $twoFactor,
            'security_hygiene' => $securityHygiene,
            'suspicious_events' => $suspiciousEvents,
            'actions' => $actions,
        ];
    }

    /**
     * @return array{requests_30d:int,failed_attempts_30d:int,locked_tokens_30d:int,verified_email_events:int}
     */
    private function fetchResetSecurityStats(int $userId): array
    {
        $cutoff = (new \DateTimeImmutable('-30 days'))->format('Y-m-d H:i:s');

        $recentStats = $this->connection->fetchAssociative(
            'SELECT
                COUNT(*) AS requests_30d,
                COALESCE(SUM(attempts), 0) AS failed_attempts_30d,
                COALESCE(SUM(CASE WHEN attempts >= :max_attempts THEN 1 ELSE 0 END), 0) AS locked_tokens_30d
             FROM password_reset_tokens
             WHERE user_id = :user_id AND created_at >= :cutoff',
            [
                'user_id' => $userId,
                'cutoff' => $cutoff,
                'max_attempts' => self::MAX_RESET_ATTEMPTS,
            ],
        );

        $verifiedEmailEvents = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM password_reset_tokens WHERE user_id = :user_id AND used = 1',
            ['user_id' => $userId],
        );

        return [
            'requests_30d' => (int) ($recentStats['requests_30d'] ?? 0),
            'failed_attempts_30d' => (int) ($recentStats['failed_attempts_30d'] ?? 0),
            'locked_tokens_30d' => (int) ($recentStats['locked_tokens_30d'] ?? 0),
            'verified_email_events' => $verifiedEmailEvents,
        ];
    }

    /**
     * @return array{points:int,max:int,percent:int}
     */
    private function computeProfileCompleteness(Utilisateur $user): array
    {
        $points = 0;

        if (trim((string) $user->getPrenomU()) !== '') {
            $points += 8;
        }

        if (trim((string) $user->getNomU()) !== '') {
            $points += 8;
        }

        $email = trim((string) $user->getEmailU());
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            $points += 8;
        }

        $age = (int) $user->getAgeU();
        if ($age >= 13 && $age <= 120) {
            $points += 8;
        }

        if (trim((string) $user->getProfile_picture_path()) !== '') {
            $points += 8;
        }

        return [
            'points' => $points,
            'max' => 40,
            'percent' => (int) round(($points / 40) * 100),
        ];
    }

    /**
     * @return array{points:int,max:int,verified:bool,note:string}
     */
    private function computeVerifiedEmailSignal(int $verifiedEmailEvents): array
    {
        $verified = $verifiedEmailEvents > 0;

        return [
            'points' => $verified ? 20 : 0,
            'max' => 20,
            'verified' => $verified,
            'note' => $verified
                ? 'Verified from successful secure email challenge history.'
                : 'No verification signal yet. Complete one reset-code flow to establish email trust.',
        ];
    }

    /**
     * @return array{points:int,max:int,enabled:bool}
     */
    private function computeTwoFactor(Utilisateur $user): array
    {
        $enabled = $user->getTotp_enabled() && trim((string) $user->getTotp_secret()) !== '';

        return [
            'points' => $enabled ? 20 : 0,
            'max' => 20,
            'enabled' => $enabled,
        ];
    }

    /**
     * @param array{requests_30d:int,failed_attempts_30d:int,locked_tokens_30d:int,verified_email_events:int} $securityStats
     *
     * @return array{points:int,max:int,face_enabled:bool,suspicious_events:int,risk_level:string}
     */
    private function computeSecurityHygiene(Utilisateur $user, array $securityStats): array
    {
        $faceEnabled = $user->getFace_enabled() === true;
        $suspiciousEvents = count($this->buildSuspiciousEvents($securityStats));

        $points = 0;
        if ($faceEnabled) {
            $points += 8;
        }

        $activityScore = 12;
        if ($securityStats['failed_attempts_30d'] >= 3) {
            $activityScore -= 4;
        }
        if ($securityStats['locked_tokens_30d'] > 0) {
            $activityScore -= 5;
        }
        if ($securityStats['requests_30d'] >= 4) {
            $activityScore -= 3;
        }
        $points += max(0, $activityScore);

        return [
            'points' => $points,
            'max' => 20,
            'face_enabled' => $faceEnabled,
            'suspicious_events' => $suspiciousEvents,
            'risk_level' => $this->riskLevel($suspiciousEvents),
        ];
    }

    /**
     * @param array{requests_30d:int,failed_attempts_30d:int,locked_tokens_30d:int,verified_email_events:int} $securityStats
     *
     * @return list<string>
     */
    private function buildSuspiciousEvents(array $securityStats): array
    {
        $events = [];

        if ($securityStats['failed_attempts_30d'] >= 3) {
            $events[] = 'Multiple reset-code verification failures detected in the last 30 days.';
        }

        if ($securityStats['locked_tokens_30d'] > 0) {
            $events[] = 'At least one reset token reached the maximum attempt threshold.';
        }

        if ($securityStats['requests_30d'] >= 4) {
            $events[] = 'High volume of password reset requests in the last 30 days.';
        }

        return $events;
    }

    /**
     * @param array{points:int,max:int,percent:int} $profileCompleteness
     * @param array{points:int,max:int,verified:bool,note:string} $verifiedEmail
     * @param array{points:int,max:int,enabled:bool} $twoFactor
     * @param list<string> $suspiciousEvents
     *
     * @return list<array{code:string,label:string,priority:string}>
     */
    private function buildActions(
        Utilisateur $user,
        array $profileCompleteness,
        array $verifiedEmail,
        array $twoFactor,
        array $suspiciousEvents,
    ): array {
        $actions = [];

        if (!$twoFactor['enabled']) {
            $actions[] = [
                'code' => 'enable_2fa',
                'label' => 'Enable two-factor authentication to protect sign-in.',
                'priority' => 'high',
            ];
        }

        if (!$verifiedEmail['verified']) {
            $actions[] = [
                'code' => 'verify_email_signal',
                'label' => 'Verify your email trust signal by completing one reset-code flow.',
                'priority' => 'high',
            ];
        }

        if (!$user->getFace_enabled()) {
            $actions[] = [
                'code' => 'enable_face_id',
                'label' => 'Activate Face ID for an additional trusted sign-in method.',
                'priority' => 'medium',
            ];
        }

        if ($profileCompleteness['percent'] < 100) {
            $actions[] = [
                'code' => 'complete_profile',
                'label' => 'Complete all profile fields to improve account integrity.',
                'priority' => 'medium',
            ];
        }

        if ($suspiciousEvents !== []) {
            $actions[] = [
                'code' => 'change_password',
                'label' => 'Change your password and review recent security activity.',
                'priority' => 'high',
            ];
        }

        if ($actions === []) {
            $actions[] = [
                'code' => 'keep_monitoring',
                'label' => 'Great job. Keep your security settings up to date.',
                'priority' => 'low',
            ];
        }

        usort(
            $actions,
            fn (array $a, array $b): int => $this->priorityWeight($a['priority']) <=> $this->priorityWeight($b['priority']),
        );

        return $actions;
    }

    private function scoreBand(int $score): string
    {
        if ($score >= 85) {
            return 'excellent';
        }

        if ($score >= 70) {
            return 'good';
        }

        if ($score >= 50) {
            return 'fair';
        }

        return 'low';
    }

    private function riskLevel(int $suspiciousEvents): string
    {
        if ($suspiciousEvents >= 2) {
            return 'high';
        }

        if ($suspiciousEvents === 1) {
            return 'medium';
        }

        return 'low';
    }

    private function priorityWeight(string $priority): int
    {
        return match ($priority) {
            'high' => 0,
            'medium' => 1,
            default => 2,
        };
    }
}
