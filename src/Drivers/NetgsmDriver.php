<?php

namespace Canopus\SmsApi\Drivers;

use Canopus\SmsApi\Contracts\SmsDriver;
use Canopus\SmsApi\Exceptions\SmsException;
use Canopus\SmsApi\SmsResponse;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Netgsm SMS sürücüsü — resmi REST v2 JSON API'sini kullanır.
 *
 * @see https://www.netgsm.com.tr/dokuman/#sms-gonderimi
 */
class NetgsmDriver implements SmsDriver
{
    private const SUCCESS_CODES = ['00', '01', '02'];

    private const ERROR_CODES = [
        '20' => 'Mesaj metninde sorun var ya da standart maksimum karakter sayısı aşıldı.',
        '30' => 'Geçersiz kullanıcı adı/şifre, API erişim izni yok ya da IP sınırlamasına takıldı.',
        '40' => 'Mesaj başlığı (gönderici adı) sistemde tanımlı değil.',
        '50' => 'Hesabınız İYS kontrollü gönderim yapamıyor.',
        '51' => 'Aboneliğinize tanımlı İYS marka bilgisi bulunamadı.',
        '70' => 'Hatalı sorgu: parametrelerden biri hatalı ya da zorunlu bir alan eksik.',
        '80' => 'Gönderim sınırı aşıldı.',
        '85' => 'Mükerrer gönderim sınırı aşıldı (aynı numaraya 1 dakikada 20\'den fazla görev).',
    ];

    public function __construct(
        protected array $config,
        protected ClientInterface $client,
    ) {
    }

    public function send(string|array $to, string $message, array $options = []): SmsResponse
    {
        $numbers = array_map('strval', (array) $to);

        $payload = [
            'msgheader' => (string) ($options['sender'] ?? $this->config['msgheader'] ?? ''),
            'messages' => array_map(
                static fn (string $no): array => ['msg' => $message, 'no' => $no],
                $numbers,
            ),
            'encoding' => (string) ($options['encoding'] ?? $this->config['encoding'] ?? 'TR'),
        ];

        $iysfilter = $options['iysfilter'] ?? $this->config['iysfilter'] ?? null;

        if ($iysfilter !== null && $iysfilter !== '') {
            $payload['iysfilter'] = (string) $iysfilter;
        }

        if (! empty($options['scheduledAt'])) {
            $payload['startdate'] = (string) $options['scheduledAt'];
        }

        if (! empty($options['scheduledUntil'])) {
            $payload['stopdate'] = (string) $options['scheduledUntil'];
        }

        if (! empty($this->config['appname'])) {
            $payload['appname'] = (string) $this->config['appname'];
        }

        try {
            $response = $this->client->post('sms/rest/v2/send', [
                'auth' => [(string) ($this->config['usercode'] ?? ''), (string) ($this->config['password'] ?? '')],
                'json' => $payload,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            throw new SmsException('Netgsm isteği başarısız oldu: '.$e->getMessage(), previous: $e);
        }

        $data = json_decode((string) $response->getBody(), true) ?? [];
        $code = (string) ($data['code'] ?? '');

        if (in_array($code, self::SUCCESS_CODES, true)) {
            return SmsResponse::success($data['jobid'] ?? null, $data);
        }

        $reason = $data['description'] ?? (self::ERROR_CODES[$code] ?? 'Bilinmeyen hata.');

        return SmsResponse::failure("Netgsm gönderim hatası ({$code}): {$reason}", $data);
    }
}
