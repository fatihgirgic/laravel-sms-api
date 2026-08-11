<?php

namespace Canopus\SmsApi\Facades;

use Canopus\SmsApi\SmsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Canopus\SmsApi\SmsResponse send(string|array $to, string $message, array $options = [])
 * @method static \Canopus\SmsApi\Contracts\SmsDriver driver(string|null $driver = null)
 *
 * @see \Canopus\SmsApi\SmsManager
 */
class Sms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SmsManager::class;
    }
}
