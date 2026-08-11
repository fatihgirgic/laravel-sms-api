<?php

namespace Canopus\SmsApi\Contracts;

use Canopus\SmsApi\SmsResponse;

interface SmsDriver
{
    /**
     * Bir veya birden fazla numaraya SMS gönderir.
     *
     * @param  string|array<int, string>  $to  Tek numara ya da numara dizisi.
     * @param  array<string, mixed>  $options  Sürücüye özel ek seçenekler (sender, scheduledAt, vb.)
     */
    public function send(string|array $to, string $message, array $options = []): SmsResponse;
}
