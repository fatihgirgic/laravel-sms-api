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
            new Response(200, [], '1:589052:Gonderildi:2:0,010'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new VatanSmsDriver([
            'account_no' => '12345',
            'username' => 'user',
            'password' => 'pass',
            'sender' => 'TEST',
        ], $client);

        $response = $driver->send(['5551112233', '5551112244'], 'Merhaba dünya');

        $this->assertTrue($response->successful());
        $this->assertSame('589052', $response->messageId);
    }

    public function test_it_reports_failure_from_error_line(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '2:Yeterli Bakiyeniz Yok'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $driver = new VatanSmsDriver([
            'account_no' => '12345',
            'username' => 'user',
            'password' => 'pass',
        ], $client);

        $response = $driver->send('5551112233', 'Merhaba');

        $this->assertTrue($response->failed());
        $this->assertSame('Yeterli Bakiyeniz Yok', $response->errorMessage);
    }
}
