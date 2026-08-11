<?php

namespace Canopus\SmsApi\Tests\Drivers;

use Canopus\SmsApi\Drivers\IletiMerkeziDriver;
use Canopus\SmsApi\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class IletiMerkeziDriverTest extends TestCase
{
    public function test_it_sends_sms_successfully(): void
    {
        $body = json_encode([
            'response' => [
                'status' => ['code' => 200, 'message' => 'Başarılı'],
                'order' => ['id' => '999'],
            ],
        ]);

        $mock = new MockHandler([new Response(200, [], $body)]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new IletiMerkeziDriver([
            'key' => 'key',
            'hash' => 'hash',
            'sender' => 'TEST',
        ], $client);

        $response = $driver->send(['905551112233'], 'Merhaba dünya');

        $this->assertTrue($response->successful());
        $this->assertSame('999', $response->messageId);
    }

    public function test_it_reports_failure_on_non_200(): void
    {
        $body = json_encode([
            'response' => [
                'status' => ['code' => 400, 'message' => 'Geçersiz kimlik doğrulama'],
            ],
        ]);

        $mock = new MockHandler([new Response(400, [], $body)]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new IletiMerkeziDriver([
            'key' => 'key',
            'hash' => 'hash',
        ], $client);

        $response = $driver->send('905551112233', 'Merhaba');

        $this->assertTrue($response->failed());
        $this->assertSame('Geçersiz kimlik doğrulama', $response->errorMessage);
    }
}
