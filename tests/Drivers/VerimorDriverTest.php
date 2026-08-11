<?php

namespace Canopus\SmsApi\Tests\Drivers;

use Canopus\SmsApi\Drivers\VerimorDriver;
use Canopus\SmsApi\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class VerimorDriverTest extends TestCase
{
    public function test_it_sends_sms_successfully(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '20212'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new VerimorDriver([
            'username' => 'user',
            'password' => 'pass',
            'source_addr' => 'TEST',
        ], $client);

        $response = $driver->send('905551112233', 'Merhaba dünya');

        $this->assertTrue($response->successful());
        $this->assertSame('20212', $response->messageId);
    }

    public function test_it_reports_failure_on_non_200(): void
    {
        $mock = new MockHandler([
            new Response(400, [], 'INSUFFICIENT_CREDITS'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new VerimorDriver([
            'username' => 'user',
            'password' => 'pass',
            'source_addr' => 'TEST',
        ], $client);

        $response = $driver->send('905551112233', 'Merhaba');

        $this->assertTrue($response->failed());
        $this->assertStringContainsString('INSUFFICIENT_CREDITS', $response->errorMessage);
    }
}
