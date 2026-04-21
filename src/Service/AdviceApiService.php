<?php
namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AdviceApiService
{
    private $httpClient;
    
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }
    
    public function getRandomAdvice(): string
    {
        $response = $this->httpClient->request('GET', 'https://api.adviceslip.com/advice');
        $data = $response->toArray();
        return $data['slip']['advice'];
    }
}