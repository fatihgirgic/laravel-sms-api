<?php

namespace Canopus\SmsApi\Tests;

use Canopus\SmsApi\Facades\Sms;
use Canopus\SmsApi\SmsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SmsServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Sms' => Sms::class,
        ];
    }
}
