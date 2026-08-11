<?php

namespace Canopus\SmsApi\Drivers;

use Canopus\SmsApi\Contracts\SmsDriver;
use Canopus\SmsApi\Exceptions\SmsException;
use Canopus\SmsApi\SmsResponse;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use SimpleXMLElement;

/**
 * Netgsm SMS sürücüsü.
 *
 * Not: İncelenen https://github.com/Resul-9/Netgsm-Api (PHP dalı) yalnızca
 * Netgsm'in eski/legacy SOAP arayüzünü örnekliyor ve herhangi bir yanıt
 * ayrıştırması içermiyor. Bu sürücü onun yerine Netgsm'in güncel, kamuya
 * açık dokümante edilmiş REST/XML gönderim uç noktasını (sms/send/xml)
 * kullanır. Hesabınızın API dokümantasyonuyla karşılaştırmanız önerilir.
 *
 * @see https://www.netgsm.com.tr/dokuman/
 */
class NetgsmDriver implements SmsDriver
{
    private const ERROR_CODES = [
        '20' => 'Mesaj metni veya karakter seti hatalı.',
        '30' => 'Geçersiz kullanıcı adı, şifre ya da API erişim izni yok.',
        '40' => 'Gönderici adı (mesaj başlığı) sisteme tanımlı değil.',
        '50' => 'Yetersiz kredi.',
        '51' => 'Gönderim bulunamadı.',
        '70' => 'Hatalı ya da eksik parametre.',
        '80' => 'Gönderim tarihi formatı hatalı.',
        '85' => 'Mükerrer gönderim.',
    ];

    public function __construct(
        protected array $config,
        protected ClientInterface $client,
    ) {
    }

    public function send(string|array $to, string $message, array $options = []): SmsResponse
    {
        $numbers = array_map('strval', (array) $to);

        $xml = new SimpleXMLElement('<mainbody/>');
        $header = $xml->addChild('header');
        $company = $header->addChild('company', 'Netgsm');
        $company->addAttribute('dil', 'TR');
        $header->addChild('usercode', (string) ($this->config['usercode'] ?? ''));
        $header->addChild('password', (string) ($this->config['password'] ?? ''));
        $header->addChild('type', '1:n');
        $header->addChild('msgheader', (string) ($options['sender'] ?? $this->config['msgheader'] ?? ''));

        if (! empty($options['scheduledAt'])) {
            $header->addChild('startdate', (string) $options['scheduledAt']);
        }

        $body = $xml->addChild('body');
        $this->appendCData($body->addChild('msg'), $message);

        foreach ($numbers as $number) {
            $body->addChild('no', $number);
        }

        try {
            $response = $this->client->post('sms/send/xml', [
                'headers' => ['Content-Type' => 'text/xml; charset=UTF-8'],
                'body' => $xml->asXML(),
            ]);
        } catch (GuzzleException $e) {
            throw new SmsException('Netgsm isteği başarısız oldu: '.$e->getMessage(), previous: $e);
        }

        $raw = trim((string) $response->getBody());
        [$code, $jobId] = array_pad(explode(' ', $raw, 2), 2, null);

        if (in_array($code, ['00', '01', '02'], true)) {
            return SmsResponse::success($jobId, ['raw' => $raw, 'code' => $code]);
        }

        $reason = self::ERROR_CODES[$code] ?? 'Bilinmeyen hata.';

        return SmsResponse::failure("Netgsm gönderim hatası ({$code}): {$reason}", ['raw' => $raw, 'code' => $code]);
    }

    private function appendCData(SimpleXMLElement $node, string $value): void
    {
        $domNode = dom_import_simplexml($node);
        $domDocument = $domNode->ownerDocument;
        $domNode->appendChild($domDocument->createCDATASection($value));
    }
}
