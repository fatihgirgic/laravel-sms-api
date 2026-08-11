<?php

namespace Canopus\SmsApi\Tests;

use Canopus\SmsApi\Drivers\IletiMerkeziDriver;
use Canopus\SmsApi\Drivers\MutlucellDriver;
use Canopus\SmsApi\SmsManager;

class SmsManagerTest extends TestCase
{
    public function test_default_driver_is_resolved_from_config(): void
    {
        config(['sms.default' => 'mutlucell']);

        $manager = $this->app->make(SmsManager::class);

        $this->assertInstanceOf(MutlucellDriver::class, $manager->driver());
    }

    public function test_it_can_resolve_a_specific_driver(): void
    {
        $manager = $this->app->make(SmsManager::class);

        $this->assertInstanceOf(IletiMerkeziDriver::class, $manager->driver('iletimerkezi'));
    }
}
