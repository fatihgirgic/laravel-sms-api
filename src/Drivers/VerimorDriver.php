<?php

namespace Canopus\SmsApi\Drivers;

use Canopus\SmsApi\Contracts\SmsDriver;
use Canopus\SmsApi\Exceptions\SmsException;
use Canopus\SmsApi\SmsResponse;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Verimor SMS API sürücüsü.
 *
 * @see https://github.com/verimor/SMS-API
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
            'source_addr' => $options['sender'] ?? $this->config['source_addr'] ?? '',
            'messages' => [
                [
                    'msg' => $message,
                    'dest' => $dest,
                ],
            ],
        ];

        if (! empty($options['customId'])) {
            $payload['custom_id'] = $options['customId'];
        }

        if (! empty($options['validFor'])) {
            $payload['valid_for'] = $options['validFor'];
        }

        if (! empty($options['scheduledAt'])) {
            $payload['send_at'] = $options['scheduledAt'];
        }

        $datacoding = $options['datacoding'] ?? $this->config['datacoding'] ?? null;

        if ($datacoding !== null) {
            $payload['datacoding'] = $datacoding;
        }

        try {
            $response = $this->client->post('send.json', [
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
