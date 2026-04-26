<?php

namespace App\Service;

use App\Exception\GoogleAuthenticationException;

final class GoogleOAuthService
{
    private const AUTHORIZE_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
    ) {
    }

    public function getAuthorizationUrl(string $state, ?string $redirectUri = null): string
    {
        $effectiveRedirectUri = $redirectUri ?? $this->redirectUri;

        if ($this->clientId === '' || $this->clientSecret === '' || $effectiveRedirectUri === '') {
            throw new GoogleAuthenticationException('Google login is not configured yet.');
        }

        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $effectiveRedirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return self::AUTHORIZE_URL . '?' . $query;
    }

    /**
     * @return array{sub: string, email: string, given_name: string, family_name: string, picture: string}
     */
    public function fetchUser(string $code, ?string $redirectUri = null): array
    {
        $effectiveRedirectUri = $redirectUri ?? $this->redirectUri;

        if ($code === '') {
            throw new GoogleAuthenticationException('Google did not return an authorization code.');
        }

        if ($effectiveRedirectUri === '') {
            throw new GoogleAuthenticationException('Google login callback URL is not configured yet.');
        }

        $tokenResponse = $this->request(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $effectiveRedirectUri,
            'grant_type' => 'authorization_code',
        ], false);

        $accessToken = (string) ($tokenResponse['access_token'] ?? '');
        if ($accessToken === '') {
            throw new GoogleAuthenticationException('Google did not return an access token.');
        }

        $userInfo = $this->request(self::USERINFO_URL, null, true, $accessToken);

        $sub = (string) ($userInfo['sub'] ?? '');
        $email = trim((string) ($userInfo['email'] ?? ''));

        if ($sub === '' || $email === '') {
            throw new GoogleAuthenticationException('Google did not return the required account details.');
        }

        return [
            'sub' => $sub,
            'email' => $email,
            'given_name' => trim((string) ($userInfo['given_name'] ?? '')),
            'family_name' => trim((string) ($userInfo['family_name'] ?? '')),
            'picture' => trim((string) ($userInfo['picture'] ?? '')),
        ];
    }

    /**
     * @param array<string, string>|null $formPayload
     *
     * @return array<string, mixed>
     */
    private function request(string $url, ?array $formPayload = null, bool $isGet = false, string $bearerToken = ''): array
    {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new GoogleAuthenticationException('Unable to initialize the Google login client.');
        }

        $headers = ['Accept: application/json'];

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
            throw new GoogleAuthenticationException('Google login request failed: ' . ($curlError !== '' ? $curlError : 'unknown network error'));
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($rawResponse, true);
        if (!is_array($decoded)) {
            throw new GoogleAuthenticationException('Google returned an invalid response.');
        }

        if ($httpCode >= 400) {
            $message = (string) ($decoded['error_description'] ?? $decoded['error'] ?? '');
            throw new GoogleAuthenticationException($message !== '' ? $message : 'Google login was rejected.');
        }

        return $decoded;
    }
}
