<?php

namespace Canopus\SmsApi\Drivers;

use Canopus\SmsApi\Contracts\SmsDriver;
use Canopus\SmsApi\Exceptions\SmsException;
use Canopus\SmsApi\SmsResponse;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use SimpleXMLElement;

/**
 * Mutlucell SMS Gateway sürücüsü.
 *
 * @see https://github.com/Ardakilic/laravel-mutlucell-sms
 */
class MutlucellDriver implements SmsDriver
{
    public function __construct(
        protected array $config,
        protected ClientInterface $client,
    ) {
    }

    public function send(string|array $to, string $message, array $options = []): SmsResponse
    {
        $numbers = implode(',', array_map('strval', (array) $to));

        $xml = new SimpleXMLElement('<smspack/>');
        $xml->addAttribute('ka', (string) ($this->config['username'] ?? ''));
        $xml->addAttribute('pwd', (string) ($this->config['password'] ?? ''));
        $xml->addAttribute('org', (string) ($options['sender'] ?? $this->config['sender'] ?? ''));
        $xml->addAttribute('charset', (string) ($this->config['charset'] ?? 'default'));

        if (! empty($options['scheduledAt'])) {
            $xml->addAttribute('tarih', (string) $options['scheduledAt']);
        }

        $mesaj = $xml->addChild('mesaj');
        $mesaj->addChild('metin', $message);
        $mesaj->addChild('nums', $numbers);

        try {
            $response = $this->client->post('sndblkex', [
                'headers' => ['Content-Type' => 'text/xml; charset=UTF8'],
                'body' => $xml->asXML(),
            ]);
        } catch (GuzzleException $e) {
            throw new SmsException('Mutlucell isteği başarısız oldu: '.$e->getMessage(), previous: $e);
        }

        $body = (string) $response->getBody();

        if (! preg_match('/(\$[0-9]+#[0-9]+\.[0-9]+)/i', $body, $matches)) {
            return SmsResponse::failure('Mutlucell yanıtı çözümlenemedi: '.$body, ['raw' => $body]);
        }

        [$messageId, $status] = explode('#', str_replace('$', '', $matches[1]));

        if ($status === '0.0') {
            return SmsResponse::failure("Mutlucell gönderim hatası (durum kodu: {$status})", ['raw' => $body]);
        }

        return SmsResponse::success($messageId, ['raw' => $body, 'status' => $status]);
    }
}
