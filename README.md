# Laravel SMS API

Türkiye'deki başlıca SMS sağlayıcılarını tek, ortak bir arayüz altında toplayan Laravel paketi. Laravel'in `Mail`/`Queue` yapısındaki gibi **driver tabanlı** çalışır: `.env` dosyasında hangi sağlayıcıyı kullanacağınızı seçersiniz, kodunuz değişmez.

Desteklenen sağlayıcılar:

- **Mutlucell**
- **Verimor**
- **Netgsm**
- **VatanSMS.net**
- **İletimerkezi**

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

# VatanSMS.net
VATANSMS_API_ID=
VATANSMS_API_KEY=
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

Bu paket, her sağlayıcının açık kaynak istemci/örnek kodları incelenerek yazılmıştır. Üretime almadan önce test hesabınızla mutlaka doğrulama yapın:

- **Netgsm**: İncelenen kaynak (`Resul-9/Netgsm-Api`, `PHP` dalı) yalnızca Netgsm'in eski/legacy SOAP arayüzünü örnekliyor ve yanıt ayrıştırması içermiyor. Bu paket bunun yerine Netgsm'in güncel, dokümante REST/XML gönderim uç noktasını (`sms/send/xml`) kullanır.
- **VatanSMS.net**: İncelenen istemci (`vayztr/vatansmsnet-php`) API yanıtını olduğu gibi diziye çevirip döndürüyor, başarı/hata alanlarının kesin şemasını belgelemiyor. Bu paket yaygın `{status, message, data}` yanıt zarfını varsayar; hesabınızda farklı bir yanıt şekli görürseniz `SmsResponse::$raw` üzerinden kendi ayrıştırmanızı yapabilirsiniz.
- **Verimor**: Gönderim yapılacak sunucu IP adresinin Verimor panelinde (`oim.verimor.com.tr`) tanımlı olması gerekir, aksi halde `401` hatası alırsınız.
- **Mutlucell, İletimerkezi**: İncelenen resmi/MIT lisanslı istemcilerdeki (`Ardakilic/laravel-mutlucell-sms`, `iletimerkezi/iletimerkezi-php`) istek/yanıt formatı birebir uygulanmıştır.

## Testler

```bash
composer install
vendor/bin/phpunit
```

Testler, her sürücü için gerçek ağ isteği atmadan Guzzle `MockHandler` ile sahte HTTP yanıtları kullanır.

## Referans alınan projeler

Bu paket aşağıdaki açık kaynak projelerin API davranışları incelenerek, sıfırdan ve tek bir ortak arayüze uyacak şekilde yeniden yazılmıştır:

- [Ardakilic/laravel-mutlucell-sms](https://github.com/Ardakilic/laravel-mutlucell-sms) (MIT)
- [verimor/SMS-API](https://github.com/verimor/SMS-API)
- [Resul-9/Netgsm-Api](https://github.com/Resul-9/Netgsm-Api/tree/PHP)
- [vayztr/vatansmsnet-php](https://github.com/vayztr/vatansmsnet-php) (MIT)
- [iletimerkezi/iletimerkezi-php](https://github.com/iletimerkezi/iletimerkezi-php) (MIT)

## Katkı

Pull request'ler ve issue'lar açıktır. Yeni bir sağlayıcı eklemek isterseniz `Canopus\SmsApi\Contracts\SmsDriver` arayüzünü implemente eden bir sınıf yazıp `SmsManager` içine `create{Sürücü}Driver()` metodu eklemeniz yeterli.

## Lisans

MIT © [Fatih GİRGİÇ](https://canopusbilisim.com.tr) — [Canopus Bilişim](https://canopusbilisim.com.tr)
