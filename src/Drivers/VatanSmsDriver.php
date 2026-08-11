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
 * kullanır. Başarılı gönderimde yanıt gövdesi, raporlama için kullanılan
 * sayısal SMS ID'sidir.
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

        try {
            $response = $this->client->post('smsgonder1Npost.php', [
                'form_params' => ['data' => $xml->asXML()],
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            throw new SmsException('VatanSMS isteği başarısız oldu: '.$e->getMessage(), previous: $e);
        }

        $raw = trim((string) $response->getBody());

        if ($response->getStatusCode() === 200 && preg_match('/^\d+$/', $raw)) {
            return SmsResponse::success($raw, ['raw' => $raw]);
        }

        return SmsResponse::failure($raw !== '' ? $raw : 'VatanSMS gönderim hatası.', ['raw' => $raw]);
    }

    private function appendCData(SimpleXMLElement $node, string $value): void
    {
        $domNode = dom_import_simplexml($node);
        $domDocument = $domNode->ownerDocument;
        $domNode->appendChild($domDocument->createCDATASection($value));
    }
}
