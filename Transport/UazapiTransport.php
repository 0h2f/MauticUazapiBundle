<?php

namespace MauticPlugin\MauticUazapiBundle\Transport;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Sms\TransportInterface;
use Psr\Log\LoggerInterface;
use MauticPlugin\MauticUazapiBundle\Config\UazapiConfiguration;

class UazapiTransport implements TransportInterface
{
    private const MIN_DELAY = 3346;
    private const MAX_DELAY = 7543;

    public function __construct(
        private UazapiConfiguration $configuration,
        private LoggerInterface $logger,
    ) {}

    public function sendSms(Lead $lead, $content)
    {
        $number = $lead->getLeadPhoneNumber();
        if (!$number) {
            return false;
        }

        try {
            $webhookUrl = $this->configuration->getApiUrl();

            if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
                $this->logger->warning('Webhook do Whatsapp não é uma URL válida.');
                return false;
            }

            $parts = array_map('trim', explode("[instancia]", $content));
            if (count($parts) < 3) {
                $this->logger->warning('Instancia não informada no conteúdo do SMS.');
                return false;
            }

            $instance = $parts[1];
            $message = $parts[2];

            [$endpoint, $payload] = $this->prepareRequest($webhookUrl, $message, $number);
            
            if (!$payload) {
                return false;
            }

            $httpCode = $this->sendApiRequest($endpoint, $payload, $instance);

            if ($httpCode >= 200 && $httpCode < 300) {
                return true;
            }

            $this->logger->error("Erro ao enviar SMS via Whatsapp: Código HTTP $httpCode", [
                'message' => $content
            ]);
            return false;

        } catch (\Exception $e) {
            $this->logger->error('Erro ao enviar SMS via Whatsapp: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $e->getMessage();
        }
    }

    private function prepareRequest(string $baseUrl, string $message, string $number): array {
        $sanitizedNumber = $this->sanitizeNumber($number);
        $delay = rand(self::MIN_DELAY, self::MAX_DELAY);

        if (preg_match('/\[btn\]\s*(.*?)\s*\[btn\](.*)/s', $message, $btnMatches)) {
            $buttonData = trim($btnMatches[1]);
            $remainingText = trim($btnMatches[2]);

            $imageUrl = null;
            if (preg_match('/\[img\]\s*(.*?)\s*\[img\](.*)/s', $message, $imgMatches)) {
                $imageUrl = trim($imgMatches[1]);
            }

            return $this->buildButtonRequest(
                $baseUrl, 
                $sanitizedNumber, 
                $remainingText, 
                $buttonData, 
                $imageUrl, 
                $delay
            );
        }
        
        if (preg_match('/\[img\]\s*(.*?)\s*\[img\](.*)/s', $message, $imgMatches)) {
            $imageUrl = trim($imgMatches[1]);
            $text = trim($imgMatches[2]);
            
            return $this->buildImageRequest(
                $baseUrl, 
                $sanitizedNumber, 
                $text, 
                $imageUrl, 
                $delay
            );
        }
        
        return $this->buildTextRequest($baseUrl, $sanitizedNumber, $message, $delay);
}

    private function buildButtonRequest(
        string $baseUrl, 
        string $number, 
        string $text, 
        string $buttonData, 
        ?string $imageUrl, 
        int $delay
    ): array {
        $endpoint = "$baseUrl/send/menu";
        $payload = [
            'number' => $number,
            'type' => 'button',
            'text' => $text,
            'delay' => $delay,
            'choices' => [$buttonData]
        ];
        
        if ($imageUrl) {
            $payload['imageButton'] = $imageUrl;
        }
        
        return [$endpoint, json_encode($payload)];
    }

    private function buildImageRequest(
        string $baseUrl, 
        string $number, 
        string $text, 
        string $imageUrl, 
        int $delay
    ): array {
        $endpoint = "$baseUrl/send/media";
        $payload = json_encode([
            'number' => $number,
            'type' => 'image',
            'file' => $imageUrl,
            'text' => $text,
            'delay' => $delay
        ]);
        
        return [$endpoint, $payload];
    }

    private function buildTextRequest(
        string $baseUrl, 
        string $number, 
        string $text, 
        int $delay
    ): array {
        $endpoint = "$baseUrl/send/text";
        $payload = json_encode([
            'number' => $number,
            'text' => $text,
            'delay' => $delay,
            'linkPreview' => true
        ]);
        
        return [$endpoint, $payload];
    }

    private function sendApiRequest(string $apiUrl, string $payload, string $instance): int
    {
        $this->logger->info('Sending request', ['url' => $apiUrl, 'payload' => $payload, 'instance' =>  $instance]);

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'token: ' . $instance,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
            ],
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            $this->logger->error('Erro ao enviar SMS via Whatsapp: ' . $error);
            curl_close($ch);
            return 500;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode;
    }

    private function sanitizeNumber(string $number): string
    {
        $util = PhoneNumberUtil::getInstance();
        $parsed = $util->parse($number, 'US');
        return str_replace('+', '', $util->format($parsed, PhoneNumberFormat::E164));
    }
}