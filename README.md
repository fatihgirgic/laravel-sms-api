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

## Sağlayıcılara özel notlar

Üretime almadan önce test hesabınızla mutlaka doğrulama yapın:

- **Netgsm**: Sağlayıcının [resmi API dokümanındaki](https://www.netgsm.com.tr/dokuman/#sms-gonderimi) REST v2 uç noktası (`POST /sms/rest/v2/send`) kullanılır. Kimlik doğrulama HTTP Basic Auth iledir (`usercode`/`password`), gövde JSON'dur. Başarı kodları `00`/`01`/`02`, hata kodları (`20`, `30`, `40`, `50`, `51`, `70`, `80`, `85`) dokümandan birebir alınmıştır.
- **VatanSMS.net**: Sağlayıcının resmi panel API'si (`panel.vatansms.com/panel/smsgonder1Npost.php`) kullanılır — tek mesajı XML içinde `data` form alanıyla POST eder. Başarılı gönderimde yanıt gövdesi, raporlama için kullanılan sayısal SMS ID'sidir; sayısal olmayan bir yanıt hata olarak değerlendirilir.
- **Verimor**: Gönderim yapılacak sunucu IP adresinin Verimor panelinde (`oim.verimor.com.tr`) tanımlı olması gerekir, aksi halde `401` hatası alırsınız.
- **Mutlucell, İletimerkezi**: Sağlayıcıların dokümante edilmiş XML/JSON istek-yanıt formatları birebir uygulanmıştır.

## Testler

```bash
composer install
vendor/bin/phpunit
```

Testler, her sürücü için gerçek ağ isteği atmadan Guzzle `MockHandler` ile sahte HTTP yanıtları kullanır.

## Kaynaklar

Bu paket, sıfırdan ve tek bir ortak arayüze uyacak şekilde aşağıdaki kaynaklar temel alınarak yazılmıştır:

- [Verimor SMS-API](https://github.com/verimor/SMS-API) — sağlayıcının resmi örnek kodları
- [Netgsm API dokümanı](https://www.netgsm.com.tr/dokuman/#sms-gonderimi) — resmi REST v2 dokümantasyonu
- VatanSMS.net — sağlayıcının resmi panel API'si
- Mutlucell, İletimerkezi — sağlayıcıların dokümante edilmiş istek/yanıt formatları

## Katkı

Pull request'ler ve issue'lar açıktır. Yeni bir sağlayıcı eklemek isterseniz `Canopus\SmsApi\Contracts\SmsDriver` arayüzünü implemente eden bir sınıf yazıp `SmsManager` içine `create{Sürücü}Driver()` metodu eklemeniz yeterli.

## Lisans

MIT © [Fatih GİRGİÇ](https://canopusbilisim.com.tr) — [Canopus Bilişim](https://canopusbilisim.com.tr)
