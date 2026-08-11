<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Varsayılan SMS Sürücüsü
    |--------------------------------------------------------------------------
    |
    | Aşağıdaki sürücülerden biri: "mutlucell", "verimor", "netgsm",
    | "vatansms", "iletimerkezi"
    |
    */

    'default' => env('SMS_DRIVER', 'iletimerkezi'),

    /*
    |--------------------------------------------------------------------------
    | Sürücü Ayarları
    |--------------------------------------------------------------------------
    */

    'drivers' => [

        'mutlucell' => [
            'username' => env('MUTLUCELL_USERNAME'),
            'password' => env('MUTLUCELL_PASSWORD'),
            'sender' => env('MUTLUCELL_SENDER'),
            'charset' => env('MUTLUCELL_CHARSET', 'default'),
            'base_uri' => env('MUTLUCELL_BASE_URI', 'https://smsgw.mutlucell.com/smsgw-ws/'),
        ],

        'verimor' => [
            'username' => env('VERIMOR_USERNAME'),
            'password' => env('VERIMOR_PASSWORD'),
            'source_addr' => env('VERIMOR_SOURCE_ADDR'),
            'datacoding' => env('VERIMOR_DATACODING'),
            'base_uri' => env('VERIMOR_BASE_URI', 'https://sms.verimor.com.tr/v2/'),
        ],

        'netgsm' => [
            'usercode' => env('NETGSM_USERCODE'),
            'password' => env('NETGSM_PASSWORD'),
            'msgheader' => env('NETGSM_MSGHEADER'),
            // "UTF-8" (Türkçe karaktersiz), "TR" (Türkçe karakter destekli), "UNICODE" (emoji destekli)
            'encoding' => env('NETGSM_ENCODING', 'TR'),
            // "0" bilgilendirme, "11" bireysele İYS kontrollü, "12" tacire İYS kontrollü
            'iysfilter' => env('NETGSM_IYSFILTER'),
            'appname' => env('NETGSM_APPNAME'),
            'base_uri' => env('NETGSM_BASE_URI', 'https://api.netgsm.com.tr/'),
        ],

        'vatansms' => [
            // Panel > Hesap bilgilerinizdeki kullanıcı no (kno)
            'account_no' => env('VATANSMS_ACCOUNT_NO'),
            'username' => env('VATANSMS_USERNAME'),
            'password' => env('VATANSMS_PASSWORD'),
            'sender' => env('VATANSMS_SENDER'),
            // "Turkce", "Normal" (İngilizce) vb. - panelinizdeki tanıma göre
            'message_type' => env('VATANSMS_MESSAGE_TYPE', 'Turkce'),
            'base_uri' => env('VATANSMS_BASE_URI', 'https://panel.vatansms.com/panel/'),
        ],

        'iletimerkezi' => [
            'key' => env('ILETIMERKEZI_KEY'),
            'hash' => env('ILETIMERKEZI_HASH'),
            'sender' => env('ILETIMERKEZI_SENDER'),
            'iys' => env('ILETIMERKEZI_IYS', true),
            'iys_list' => env('ILETIMERKEZI_IYS_LIST', 'BIREYSEL'),
            'base_uri' => env('ILETIMERKEZI_BASE_URI', 'https://api.iletimerkezi.com/v1/'),
        ],

    ],

];
