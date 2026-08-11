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
            new Response(200, [], '4815162'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new VatanSmsDriver([
            'account_no' => '12345',
            'username' => 'user',
            'password' => 'pass',
            'sender' => 'TEST',
        ], $client);

        $response = $driver->send(['905551112233'], 'Merhaba dünya');

        $this->assertTrue($response->successful());
        $this->assertSame('4815162', $response->messageId);
    }

    public function test_it_reports_failure_on_non_numeric_response(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'HATA: Yetersiz bakiye'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new VatanSmsDriver([
            'account_no' => '12345',
            'username' => 'user',
            'password' => 'pass',
        ], $client);

        $response = $driver->send('905551112233', 'Merhaba');

        $this->assertTrue($response->failed());
        $this->assertSame('HATA: Yetersiz bakiye', $response->errorMessage);
    }
}
