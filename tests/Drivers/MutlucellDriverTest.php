<?php

namespace Canopus\SmsApi\Tests\Drivers;

use Canopus\SmsApi\Drivers\MutlucellDriver;
use Canopus\SmsApi\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class MutlucellDriverTest extends TestCase
{
    public function test_it_sends_sms_successfully(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '$123456#1.0'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new MutlucellDriver([
            'username' => 'user',
            'password' => 'pass',
            'sender' => 'TEST',
            'charset' => 'default',
        ], $client);

        $response = $driver->send('905551112233', 'Merhaba dünya');

        $this->assertTrue($response->successful());
        $this->assertSame('123456', $response->messageId);
    }

    public function test_it_reports_failure_on_zero_status(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '$0#0.0'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new MutlucellDriver([
            'username' => 'user',
            'password' => 'pass',
        ], $client);

        $response = $driver->send(['905551112233', '905551112244'], 'Merhaba');

        $this->assertTrue($response->failed());
    }
}
