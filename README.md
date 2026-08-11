# Laravel SMS API

Türkiye'deki başlıca SMS sağlayıcılarını tek, ortak bir arayüz altında toplayan Laravel paketi. Laravel'in `Mail`/`Queue` yapısındaki gibi **driver tabanlı** çalışır: `.env` dosyasında hangi sağlayıcıyı kullanacağınızı seçersiniz, kodunuz değişmez.

Desteklenen sağlayıcılar:

| Sağlayıcı | Web sitesi |
|---|---|
| Mutlucell | [mutlucell.com.tr](https://mutlucell.com.tr) |
| Verimor | [verimor.com.tr](https://verimor.com.tr) |
| Netgsm | [netgsm.com.tr](https://netgsm.com.tr) |
| VatanSMS.net | [vatansms.net](https://vatansms.net) |
| İletimerkezi | [iletimerkezi.com](https://iletimerkezi.com) |

Geliştiren: **Fatih GİRGİÇ** — [Canopus Bilişim](https://canopusbilisim.com.tr)

---

## Kurulum

```bash
composer require fatihgirgic/laravel-sms-api
```

Laravel paket otomatik keşfi (package auto-discovery) sayesinde servis sağlayıcı ve `Sms` facade'i otomatik olarak yüklenir. Config dosyasını yayımlamak isterseniz:

```bash
php artisan vendor:publish --tag=sms-config
```

Bu komut `config/sms.php` dosyasını oluşturur.

## Yapılandırma

`.env` dosyanıza kullanacağınız sağlayıcıyı ve o sağlayıcının bilgilerini ekleyin:

```dotenv
SMS_DRIVER=iletimerkezi

# Mutlucell
MUTLUCELL_USERNAME=
MUTLUCELL_PASSWORD=
MUTLUCELL_SENDER=

# Verimor
VERIMOR_USERNAME=
VERIMOR_PASSWORD=
VERIMOR_SOURCE_ADDR=

# Netgsm
NETGSM_USERCODE=
NETGSM_PASSWORD=
NETGSM_MSGHEADER=
NETGSM_ENCODING=TR

# VatanSMS.net
VATANSMS_ACCOUNT_NO=
VATANSMS_USERNAME=
VATANSMS_PASSWORD=
VATANSMS_SENDER=

# İletimerkezi
ILETIMERKEZI_KEY=
ILETIMERKEZI_HASH=
ILETIMERKEZI_SENDER=
```

Tüm ayarların tam listesi için [`config/sms.php`](config/sms.php) dosyasına bakın.

## Kullanım

### Facade ile

```php
use Canopus\SmsApi\Facades\Sms;

// .env'de tanımlı varsayılan sürücü ile gönderim
$response = Sms::send('905551112233', 'Merhaba dünya!');

// Birden fazla numaraya aynı mesaj
$response = Sms::send(['905551112233', '905551112244'], 'Merhaba dünya!');

// Belirli bir sürücüyü açıkça seçmek
$response = Sms::driver('netgsm')->send('905551112233', 'Merhaba!');

if ($response->successful()) {
    logger()->info('SMS gönderildi', ['messageId' => $response->messageId]);
} else {
    logger()->error('SMS gönderilemedi', ['hata' => $response->errorMessage]);
}
```

### Dependency Injection ile

```php
use Canopus\SmsApi\SmsManager;

class BildirimServisi
{
    public function __construct(private SmsManager $sms) {}

    public function dogrulamaKoduGonder(string $telefon, string $kod): void
    {
        $this->sms->send($telefon, "Doğrulama kodunuz: {$kod}");
    }
}
```

### `SmsResponse`

Her sürücünün `send()` metodu, sağlayıcı ne olursa olsun aynı şekle sahip bir `Canopus\SmsApi\SmsResponse` nesnesi döner:

| Alan | Açıklama |
|---|---|
| `successful()` / `failed()` | Gönderim başarılı mı? |
| `messageId` | Sağlayıcının döndürdüğü mesaj/sipariş kimliği (varsa) |
| `errorMessage` | Hata durumunda sağlayıcıdan gelen açıklama |
| `raw` | Sağlayıcının ham yanıtı (ek alanlara erişmek için) |

### Ek seçenekler

```php
Sms::send('905551112233', 'Merhaba!', [
    'sender' => 'FIRMA',        // gönderici adı, config'teki varsayılanı ezer
    'scheduledAt' => '2026-08-12 10:00',
]);
```

> `scheduledAt` formatı sağlayıcıya göre değişir — aşağıdaki tabloya bakın. Yanlış formatta gönderirseniz sağlayıcı hata döner.

## Sağlayıcılara özel notlar

Bu paket, her sağlayıcının resmi API dokümanları incelenerek yazılmış ve doğrulanmıştır. Üretime almadan önce yine de test hesabınızla doğrulama yapmanızı öneririz.

| Sağlayıcı | Uç nokta | `scheduledAt` formatı | Not |
|---|---|---|---|
| Mutlucell | `POST smsgw-ws/sndblkex` (XML) | serbest metin (`tarih` XML özniteliği) | — |
| Verimor | `POST /v2/send` (JSON) | ISO 8601 veya `YYYY-MM-DD HH:mm:ss` | Gönderim yapılacak sunucu IP'si Verimor panelinde (`oim.verimor.com.tr`) tanımlı olmalı, aksi halde `401` alırsınız. |
| Netgsm | `POST /sms/rest/v2/send` (JSON, HTTP Basic Auth) | `ddMMyyyyHHmm` | Başarı kodları `00`/`01`/`02`; hata kodları (`20`,`30`,`40`,`50`,`51`,`70`,`80`,`85`) [resmi dokümandan](https://www.netgsm.com.tr/dokuman/#sms-gonderimi) birebir alınmıştır. |
| VatanSMS.net | `POST panel/smsgonder1Npost.php` (XML) | `yyyy-MM-dd HH:mm:ss` | Numaralar 10 haneli, ülke kodsuz yazılır (`5xxxxxxxxx`). Yanıt `1:özelkod:açıklama:...` (başarı) veya `2:açıklama` (hata) biçiminde tek satırdır. |
| İletimerkezi | `POST /v1/send-sms/json` | `DD/MM/YYYY HH:mm` | `sendDateTime` alanı API'de dizi bekler (`["12/08/2026 10:00"]`); bu paket `scheduledAt` değerinizi otomatik olarak diziye sarar. |

## Testler

```bash
composer install
vendor/bin/phpunit
```

Testler, her sürücü için gerçek ağ isteği atmadan Guzzle `MockHandler` ile sahte HTTP yanıtları kullanır.

## Kaynaklar

Bu paket, sıfırdan ve tek bir ortak arayüze uyacak şekilde, her sağlayıcının resmi API dokümanı temel alınarak yazılmış ve bu dokümanlarla karşılaştırılarak doğrulanmıştır:

- [Verimor SMS API](https://developer.verimor.com.tr/smsapi) — resmi API referansı
- [Netgsm API dokümanı](https://www.netgsm.com.tr/dokuman/#api-dokumani) — resmi REST v2 dokümantasyonu
- [VatanSMS SMS API](https://vatansms.com/toplu-sms/sms-api/) — resmi API referansı
- [İletimerkezi SMS API](https://toplusmsapi.com/sms/gonder/json) — resmi API referansı
- Mutlucell — sağlayıcının dokümante edilmiş XML istek/yanıt formatı

## Katkı

Pull request'ler ve issue'lar açıktır. Yeni bir sağlayıcı eklemek isterseniz `Canopus\SmsApi\Contracts\SmsDriver` arayüzünü implemente eden bir sınıf yazıp `SmsManager` içine `create{Sürücü}Driver()` metodu eklemeniz yeterli.

## Lisans

MIT © [Fatih GİRGİÇ](https://canopusbilisim.com.tr) — [Canopus Bilişim](https://canopusbilisim.com.tr)
