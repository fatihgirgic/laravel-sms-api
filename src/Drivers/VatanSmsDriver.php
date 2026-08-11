<?php

namespace Canopus\SmsApi\Drivers;

use Canopus\SmsApi\Contracts\SmsDriver;
use Canopus\SmsApi\Exceptions\SmsException;
use Canopus\SmsApi\SmsResponse;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use SimpleXMLElement;

/**
 * VatanSMS (panel.vatansms.com) sürücüsü.
 *
 * Resmi "1 mesaj -> N numara" XML/POST uç noktasını (smsgonder1Npost.php)
 * kullanır. Yanıt "durum:özelkod:açıklama:kişi_sayısı:tutar" biçiminde tek
 * satırdır; "1:" ile başlayan yanıt başarı (özel kod = SMS ID), "2:" ile
 * başlayan yanıt hatadır.
 *
 * @see https://vatansms.com/toplu-sms/sms-api/
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
        $numbers = implode(',', array_map('strval', (array) $to));

        $xml = new SimpleXMLElement('<sms/>');
        $xml->addChild('kno', (string) ($this->config['account_no'] ?? ''));
        $xml->addChild('kulad', (string) ($this->config['username'] ?? ''));
        $xml->addChild('sifre', (string) ($this->config['password'] ?? ''));
        $xml->addChild('gonderen', (string) ($options['sender'] ?? $this->config['sender'] ?? ''));
        $this->appendCData($xml->addChild('mesaj'), $message);
        $xml->addChild('numaralar', $numbers);
        $xml->addChild('tur', (string) ($options['messageType'] ?? $this->config['message_type'] ?? 'Turkce'));

        if (! empty($options['scheduledAt'])) {
            $xml->addChild('zaman', (string) $options['scheduledAt']);
        }

        if (! empty($options['expiresAt'])) {
            $xml->addChild('zamanasimi', (string) $options['expiresAt']);
        }

        try {
            $response = $this->client->post('smsgonder1Npost.php', [
                'form_params' => ['data' => $xml->asXML()],
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            throw new SmsException('VatanSMS isteği başarısız oldu: '.$e->getMessage(), previous: $e);
        }

        $raw = trim((string) $response->getBody());
        $parts = explode(':', $raw);

        if (($parts[0] ?? null) === '1') {
            $messageId = $parts[1] ?? null;

            return SmsResponse::success($messageId !== '' ? $messageId : null, ['raw' => $raw]);
        }

        $reason = count($parts) > 1 ? implode(':', array_slice($parts, 1)) : $raw;

        return SmsResponse::failure($reason !== '' ? $reason : 'VatanSMS gönderim hatası.', ['raw' => $raw]);
    }

    private function appendCData(SimpleXMLElement $node, string $value): void
    {
        $domNode = dom_import_simplexml($node);
        $domDocument = $domNode->ownerDocument;
        $domNode->appendChild($domDocument->createCDATASection($value));
    }
}
