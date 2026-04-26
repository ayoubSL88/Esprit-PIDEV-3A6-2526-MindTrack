<?php

namespace App\Service;

use App\Exception\GitHubAuthenticationException;

final class GitHubOAuthService
{
    private const AUTHORIZE_URL = 'https://github.com/login/oauth/authorize';
    private const TOKEN_URL = 'https://github.com/login/oauth/access_token';
    private const USER_URL = 'https://api.github.com/user';
    private const USER_EMAILS_URL = 'https://api.github.com/user/emails';

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
    ) {
    }

    public function getAuthorizationUrl(string $state, ?string $redirectUri = null): string
    {
        $effectiveRedirectUri = $redirectUri ?? $this->redirectUri;

        $missing = [];
        if (trim($this->clientId) === '') {
            $missing[] = 'GITHUB_CLIENT_ID';
        }
        if (trim($effectiveRedirectUri) === '') {
            $missing[] = 'GITHUB_REDIRECT_URI';
        }

        if ($missing !== []) {
            throw new GitHubAuthenticationException('GitHub login is not configured yet. Missing: ' . implode(', ', $missing));
        }

        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $effectiveRedirectUri,
            'scope' => 'read:user user:email',
            'state' => $state,
        ]);

        return self::AUTHORIZE_URL . '?' . $query;
    }

    /**
     * @return array{id: string, login: string, email: string, given_name: string, family_name: string, picture: string}
     */
    public function fetchUser(string $code, ?string $redirectUri = null): array
    {
        $effectiveRedirectUri = $redirectUri ?? $this->redirectUri;

        $missing = [];
        if (trim($this->clientId) === '') {
            $missing[] = 'GITHUB_CLIENT_ID';
        }
        if (trim($this->clientSecret) === '') {
            $missing[] = 'GITHUB_CLIENT_SECRET';
        }
        if (trim($effectiveRedirectUri) === '') {
            $missing[] = 'GITHUB_REDIRECT_URI';
        }

        if ($missing !== []) {
            throw new GitHubAuthenticationException('GitHub login is not configured yet. Missing: ' . implode(', ', $missing));
        }

        if ($code === '') {
            throw new GitHubAuthenticationException('GitHub did not return an authorization code.');
        }

        $tokenResponse = $this->request(self::TOKEN_URL, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $effectiveRedirectUri,
        ], false);

        $accessToken = (string) ($tokenResponse['access_token'] ?? '');
        if ($accessToken === '') {
            throw new GitHubAuthenticationException('GitHub did not return an access token.');
        }

        $userInfo = $this->request(self::USER_URL, null, true, $accessToken);

        $email = trim((string) ($userInfo['email'] ?? ''));
        if ($email === '') {
            $email = $this->fetchPrimaryEmail($accessToken);
        }

        $id = (string) ($userInfo['id'] ?? '');
        $login = trim((string) ($userInfo['login'] ?? ''));

        if ($id === '' || $email === '') {
            throw new GitHubAuthenticationException('GitHub did not return the required account details.');
        }

        $name = trim((string) ($userInfo['name'] ?? ''));
        [$givenName, $familyName] = $this->splitName($name);

        return [
            'id' => $id,
            'login' => $login,
            'email' => $email,
            'given_name' => $givenName,
            'family_name' => $familyName,
            'picture' => trim((string) ($userInfo['avatar_url'] ?? '')),
        ];
    }

    private function fetchPrimaryEmail(string $accessToken): string
    {
        $emails = $this->request(self::USER_EMAILS_URL, null, true, $accessToken);

        if (!is_array($emails)) {
            throw new GitHubAuthenticationException('GitHub returned an invalid email payload.');
        }

        foreach ($emails as $emailRecord) {
            if (!is_array($emailRecord)) {
                continue;
            }

            $email = trim((string) ($emailRecord['email'] ?? ''));
            $isPrimary = (bool) ($emailRecord['primary'] ?? false);
            $isVerified = (bool) ($emailRecord['verified'] ?? false);

            if ($email !== '' && $isPrimary && $isVerified) {
                return $email;
            }
        }

        foreach ($emails as $emailRecord) {
            if (!is_array($emailRecord)) {
                continue;
            }

            $email = trim((string) ($emailRecord['email'] ?? ''));
            $isVerified = (bool) ($emailRecord['verified'] ?? false);

            if ($email !== '' && $isVerified) {
                return $email;
            }
        }

        throw new GitHubAuthenticationException('No verified email is available on your GitHub account.');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        if ($name === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        if (count($parts) === 1) {
            return [$parts[0], ''];
        }

        $givenName = array_shift($parts);
        $familyName = implode(' ', $parts);

        return [$givenName !== null ? $givenName : '', $familyName];
    }

    /**
     * @param array<string, string>|null $formPayload
     *
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    private function request(string $url, ?array $formPayload = null, bool $isGet = false, string $bearerToken = ''): array
    {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new GitHubAuthenticationException('Unable to initialize the GitHub login client.');
        }

        $headers = [
            'Accept: application/json',
            'User-Agent: MindTrack-GitHubOAuth',
            'X-GitHub-Api-Version: 2022-11-28',
        ];

        if ($isGet) {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
            if ($bearerToken !== '') {
                $headers[] = 'Authorization: Bearer ' . $bearerToken;
            }
        } else {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($formPayload ?? []));
        }

        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);

        $rawResponse = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($rawResponse === false) {
            throw new GitHubAuthenticationException('GitHub login request failed: ' . ($curlError !== '' ? $curlError : 'unknown network error'));
        }

        /** @var array<string, mixed>|list<array<string, mixed>>|null $decoded */
        $decoded = json_decode($rawResponse, true);
        if (!is_array($decoded)) {
            throw new GitHubAuthenticationException('GitHub returned an invalid response.');
        }

        if ($httpCode >= 400) {
            $message = '';
            if (array_is_list($decoded) && isset($decoded[0]) && is_array($decoded[0])) {
                $message = trim((string) ($decoded[0]['message'] ?? ''));
            } else {
                $message = trim((string) ($decoded['error_description'] ?? $decoded['message'] ?? $decoded['error'] ?? ''));
             }

            throw new GitHubAuthenticationException($message !== '' ? $message : 'GitHub login was rejected.');
         }
 
         return $decoded;
     }
 }
