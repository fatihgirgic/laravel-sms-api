<?php

namespace Canopus\SmsApi\Tests\Drivers;

use Canopus\SmsApi\Drivers\VatanSmsDriver;
use Canopus\SmsApi\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class VatanSmsDriverTest extends TestCase
{
    public function test_it_sends_sms_successfully(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 200, 'message' => 'OK', 'data' => ['id' => 'abc123']])),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new VatanSmsDriver([
            'api_id' => 'id',
            'api_key' => 'key',
            'sender' => 'TEST',
        ], $client);

        $response = $driver->send(['905551112233'], 'Merhaba dünya');

        $this->assertTrue($response->successful());
        $this->assertSame('abc123', $response->messageId);
    }

    public function test_it_reports_failure_from_status_field(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'error', 'message' => 'Yetersiz bakiye'])),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new VatanSmsDriver([
            'api_id' => 'id',
            'api_key' => 'key',
        ], $client);

        $response = $driver->send('905551112233', 'Merhaba');

        $this->assertTrue($response->failed());
        $this->assertSame('Yetersiz bakiye', $response->errorMessage);
    }
}
