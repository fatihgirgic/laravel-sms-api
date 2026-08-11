<?php

namespace Canopus\SmsApi\Drivers;

use Canopus\SmsApi\Contracts\SmsDriver;
use Canopus\SmsApi\Exceptions\SmsException;
use Canopus\SmsApi\SmsResponse;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * VatanSMS.net sürücüsü.
 *
 * Not: Kaynak alınan https://github.com/vayztr/vatansmsnet-php istemcisi,
 * API'nin JSON yanıtını olduğu gibi diziye çevirip döndürüyor; başarı/hata
 * alanlarının kesin adlarını belgelemiyor. Bu sürücü VatanSMS'in yaygın
 * {status, message, data} zarfını varsayarak ayrıştırma yapar. Hesabınızda
 * farklı bir yanıt şekli görürseniz raw dizisi üzerinden kendi
 * ayrıştırmanızı yapabilirsiniz (SmsResponse::$raw).
 *
 * @see https://github.com/vayztr/vatansmsnet-php
 */
class VatanSmsDriver implements SmsDriver
{
    public function __construct(
        protected array $config,
        protected ClientInterface $client,
    ) {
    }

    public function send(string|array $to, string $message, array $options = []): SmsResponse
    {
        $payload = [
            'api_id' => $this->config['api_id'] ?? '',
            'api_key' => $this->config['api_key'] ?? '',
            'phones' => array_values(array_map('strval', (array) $to)),
            'message' => $message,
            'sender' => $options['sender'] ?? $this->config['sender'] ?? '',
            'message_type' => $options['messageType'] ?? $this->config['message_type'] ?? 'normal',
            'message_content_type' => $options['messageContentType'] ?? $this->config['message_content_type'] ?? 'bilgi',
        ];

        if (! empty($options['scheduledAt'])) {
            $payload['send_time'] = $options['scheduledAt'];
        }

        try {
            $response = $this->client->post('1toN', [
                'json' => $payload,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            throw new SmsException('VatanSMS isteği başarısız oldu: '.$e->getMessage(), previous: $e);
        }

        $httpStatus = $response->getStatusCode();
        $rawBody = (string) $response->getBody();
        $data = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return SmsResponse::failure('VatanSMS yanıtı çözümlenemedi.', ['raw' => $rawBody]);
        }

        $data ??= [];
        $status = $data['status'] ?? null;
        $successful = $httpStatus === 200
            && $status !== false
            && ! in_array($status, [0, '0', 'error', 'fail', 'failed'], true);

        if (! $successful) {
            $errorMessage = $data['message'] ?? "VatanSMS gönderim hatası (HTTP {$httpStatus}).";

            return SmsResponse::failure($errorMessage, $data);
        }

        $messageId = $data['data']['id'] ?? $data['id'] ?? null;

        return SmsResponse::success($messageId !== null ? (string) $messageId : null, $data);
    }
}
