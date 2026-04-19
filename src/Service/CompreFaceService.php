<?php

namespace App\Service;

use App\Exception\FaceAuthenticationException;

final class CompreFaceService
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        string $similarityThreshold,
        string $detectionThreshold,
    ) {
        $this->similarityThreshold = (float) $similarityThreshold;
        $this->detectionThreshold = (float) $detectionThreshold;
    }

    private readonly float $similarityThreshold;

    private readonly float $detectionThreshold;

    /**
     * @return array{image_id: string, subject: string}
     */
    public function enrollFace(string $subject, string $image): array
    {
        $response = $this->request(
            'POST',
            sprintf('/api/v1/recognition/faces?subject=%s&det_prob_threshold=%s', rawurlencode($subject), $this->detectionThreshold),
            ['file' => $this->normalizeImage($image)],
        );

        $imageId = (string) ($response['image_id'] ?? '');
        $savedSubject = (string) ($response['subject'] ?? '');

        if ($imageId === '' || $savedSubject === '') {
            throw new FaceAuthenticationException('Face ID enrollment did not return a valid subject record.');
        }

        return [
            'image_id' => $imageId,
            'subject' => $savedSubject,
        ];
    }

    /**
     * @return array{matched: bool, similarity: float}
     */
    public function verifyFace(string $imageId, string $image): array
    {
        $response = $this->request(
            'POST',
            sprintf('/api/v1/recognition/faces/%s/verify?limit=1&det_prob_threshold=%s', rawurlencode($imageId), $this->detectionThreshold),
            ['file' => $this->normalizeImage($image)],
        );

        $result = $response['result'][0] ?? null;
        if (!is_array($result)) {
            throw new FaceAuthenticationException('No face was detected in the verification image.');
        }

        $similarity = (float) ($result['similarity'] ?? 0.0);

        return [
            'matched' => $similarity >= $this->similarityThreshold,
            'similarity' => $similarity,
        ];
    }

    /**
     * @return array{matched: bool, subject: string, similarity: float}
     */
    public function recognizeFace(string $image): array
    {
        $response = $this->request(
            'POST',
            sprintf('/api/v1/recognition/recognize?limit=1&det_prob_threshold=%s&prediction_count=1', $this->detectionThreshold),
            ['file' => $this->normalizeImage($image)],
        );

        $result = $response['result'][0] ?? null;
        if (!is_array($result)) {
            throw new FaceAuthenticationException('No face was detected in the verification image.');
        }

        $subjects = $result['subjects'] ?? [];
        $topSubject = is_array($subjects) ? ($subjects[0] ?? null) : null;
        if (!is_array($topSubject)) {
            return [
                'matched' => false,
                'subject' => '',
                'similarity' => 0.0,
            ];
        }

        $similarity = (float) ($topSubject['similarity'] ?? 0.0);

        return [
            'matched' => $similarity >= $this->similarityThreshold,
            'subject' => (string) ($topSubject['subject'] ?? ''),
            'similarity' => $similarity,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload): array
    {
        if ($this->baseUrl === '' || $this->apiKey === '') {
            throw new FaceAuthenticationException('Face ID is not configured yet. Add your CompreFace URL and API key in environment variables.');
        }

        $url = rtrim($this->baseUrl, '/') . $path;
        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);

        $curl = curl_init($url);
        if ($curl === false) {
            throw new FaceAuthenticationException('Unable to initialize the Face ID client.');
        }

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);

        $rawResponse = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($rawResponse === false) {
            throw new FaceAuthenticationException('Face ID request failed: ' . ($curlError !== '' ? $curlError : 'unknown network error'));
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($rawResponse, true);

        if ($httpCode >= 400) {
            $message = is_array($decoded) ? (string) ($decoded['message'] ?? $decoded['error'] ?? '') : '';
            throw new FaceAuthenticationException($message !== '' ? $message : 'Face ID request was rejected by CompreFace.');
        }

        if (!is_array($decoded)) {
            throw new FaceAuthenticationException('Face ID returned an invalid response.');
        }

        return $decoded;
    }

    private function normalizeImage(string $image): string
    {
        $normalized = trim($image);
        if ($normalized === '') {
            throw new FaceAuthenticationException('A face capture is required.');
        }

        if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,(.+)$/', $normalized, $matches) === 1) {
            $normalized = $matches[1];
        }

        $normalized = preg_replace('/\s+/', '', $normalized) ?? '';
        if ($normalized === '') {
            throw new FaceAuthenticationException('The face capture could not be processed.');
        }

        return $normalized;
    }
}
