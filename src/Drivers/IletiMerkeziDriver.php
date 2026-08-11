<?php

namespace Canopus\SmsApi\Drivers;

use Canopus\SmsApi\Contracts\SmsDriver;
use Canopus\SmsApi\Exceptions\SmsException;
use Canopus\SmsApi\SmsResponse;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * İletimerkezi sürücüsü.
 *
 * @see https://github.com/iletimerkezi/iletimerkezi-php
 */
class IletiMerkeziDriver implements SmsDriver
{
    public function __construct(
        protected array $config,
        protected ClientInterface $client,
    ) {
    }

    public function send(string|array $to, string $message, array $options = []): SmsResponse
    {
        $numbers = array_values(array_map('strval', (array) $to));

        $order = [
            'sender' => $options['sender'] ?? $this->config['sender'] ?? '',
            'sendDateTime' => $options['scheduledAt'] ?? '',
            'iys' => ($options['iys'] ?? $this->config['iys'] ?? true) ? 1 : 0,
            'iysList' => $options['iysList'] ?? $this->config['iys_list'] ?? 'BIREYSEL',
            'message' => [
                'text' => $message,
                'receipents' => [
                    'number' => $numbers,
                ],
            ],
        ];

        $payload = [
            'request' => [
                'authentication' => [
                    'key' => $this->config['key'] ?? '',
                    'hash' => $this->config['hash'] ?? '',
                ],
                'order' => $order,
            ],
        ];

        try {
            $response = $this->client->post('send-sms/json', [
                'json' => $payload,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            throw new SmsException('İletimerkezi isteği başarısız oldu: '.$e->getMessage(), previous: $e);
        }

        $httpStatus = $response->getStatusCode();
        $data = json_decode((string) $response->getBody(), true) ?? [];
        $responseBody = $data['response'] ?? [];

        if ($httpStatus !== 200) {
            $errorMessage = $responseBody['status']['message'] ?? "İletimerkezi gönderim hatası (HTTP {$httpStatus}).";

            return SmsResponse::failure($errorMessage, $responseBody);
        }

        $orderId = $responseBody['order']['id'] ?? null;

        return SmsResponse::success($orderId !== null ? (string) $orderId : null, $responseBody);
    }
}
