<?php

namespace Canopus\SmsApi\Drivers;

use Canopus\SmsApi\Contracts\SmsDriver;
use Canopus\SmsApi\Exceptions\SmsException;
use Canopus\SmsApi\SmsResponse;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Verimor SMS API sürücüsü — resmi "Birden Çok Numara Bir Mesaj
 * Gönderebilme" (POST /v2/send, JSON) uç noktasını kullanır.
 *
 * @see https://developer.verimor.com.tr/smsapi
 */
class VerimorDriver implements SmsDriver
{
    public function __construct(
        protected array $config,
        protected ClientInterface $client,
    ) {
    }

    public function send(string|array $to, string $message, array $options = []): SmsResponse
    {
        $dest = implode(',', array_map('strval', (array) $to));

        $payload = [
            'username' => $this->config['username'] ?? '',
            'password' => $this->config['password'] ?? '',
            'dest' => $dest,
            'msg' => $message,
            'source_addr' => $options['sender'] ?? $this->config['source_addr'] ?? '',
        ];

        if (! empty($options['validFor'])) {
            $payload['valid_for'] = $options['validFor'];
        }

        $datacoding = $options['datacoding'] ?? $this->config['datacoding'] ?? null;

        if ($datacoding !== null) {
            $payload['datacoding'] = (int) $datacoding;
        }

        if (array_key_exists('isCommercial', $options)) {
            $payload['is_commercial'] = (bool) $options['isCommercial'];
        }

        if (! empty($options['iysRecipientType'])) {
            $payload['iys_recipient_type'] = $options['iysRecipientType'];
        }

        if (! empty($options['scheduledAt'])) {
            $payload['send_at'] = $options['scheduledAt'];
        }

        try {
            $response = $this->client->post('send', [
                'json' => $payload,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            throw new SmsException('Verimor isteği başarısız oldu: '.$e->getMessage(), previous: $e);
        }

        $status = $response->getStatusCode();
        $body = trim((string) $response->getBody());

        if ($status === 200) {
            return SmsResponse::success($body ?: null, ['raw' => $body, 'status' => $status]);
        }

        return SmsResponse::failure("Verimor gönderim hatası ({$status}): {$body}", ['raw' => $body, 'status' => $status]);
    }
}
