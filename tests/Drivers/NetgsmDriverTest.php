<?php

namespace Canopus\SmsApi\Tests\Drivers;

use Canopus\SmsApi\Drivers\NetgsmDriver;
use Canopus\SmsApi\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class NetgsmDriverTest extends TestCase
{
    public function test_it_sends_sms_successfully(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '00 1707039719'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new NetgsmDriver([
            'usercode' => 'user',
            'password' => 'pass',
            'msgheader' => 'TEST',
        ], $client);

        $response = $driver->send('905551112233', 'Merhaba dünya');

        $this->assertTrue($response->successful());
        $this->assertSame('1707039719', $response->messageId);
    }

    public function test_it_reports_known_error_code(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '50'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new NetgsmDriver([
            'usercode' => 'user',
            'password' => 'pass',
        ], $client);

        $response = $driver->send(['905551112233'], 'Merhaba');

        $this->assertTrue($response->failed());
        $this->assertStringContainsString('Yetersiz kredi', $response->errorMessage);
    }
}
