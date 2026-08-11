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
        $body = json_encode([
            'code' => '00',
            'jobid' => '17377215342605050417149344',
            'description' => 'queued',
        ]);

        $mock = new MockHandler([new Response(200, [], $body)]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new NetgsmDriver([
            'usercode' => '8501234567',
            'password' => 'pass',
            'msgheader' => 'TEST',
        ], $client);

        $response = $driver->send('905551112233', 'Merhaba dünya');

        $this->assertTrue($response->successful());
        $this->assertSame('17377215342605050417149344', $response->messageId);
    }

    public function test_it_reports_known_error_code(): void
    {
        $body = json_encode([
            'code' => '50',
            'jobid' => null,
            'description' => 'İYS kontrollü gönderim yapılamıyor.',
        ]);

        $mock = new MockHandler([new Response(200, [], $body)]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new NetgsmDriver([
            'usercode' => '8501234567',
            'password' => 'pass',
        ], $client);

        $response = $driver->send(['905551112233'], 'Merhaba');

        $this->assertTrue($response->failed());
        $this->assertStringContainsString('İYS kontrollü gönderim yapılamıyor', $response->errorMessage);
    }
}
