<?php

namespace Canopus\SmsApi;

use Canopus\SmsApi\Drivers\IletiMerkeziDriver;
use Canopus\SmsApi\Drivers\MutlucellDriver;
use Canopus\SmsApi\Drivers\NetgsmDriver;
use Canopus\SmsApi\Drivers\VatanSmsDriver;
use Canopus\SmsApi\Drivers\VerimorDriver;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Manager;

/**
 * @method \Canopus\SmsApi\SmsResponse send(string|array $to, string $message, array $options = [])
 */
class SmsManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config('sms.default', 'iletimerkezi');
    }

    protected function config(string $key, mixed $default = null): mixed
    {
        return $this->container->make('config')->get($key, $default);
    }

    protected function driverConfig(string $driver): array
    {
        return (array) $this->config("sms.drivers.{$driver}", []);
    }

    protected function httpClient(array $config): ClientInterface
    {
        return new Client(array_filter([
            'base_uri' => $config['base_uri'] ?? null,
            'timeout' => $config['timeout'] ?? 15,
        ]));
    }

    protected function createMutlucellDriver(): MutlucellDriver
    {
        $config = $this->driverConfig('mutlucell');

        return new MutlucellDriver($config, $this->httpClient($config));
    }

    protected function createVerimorDriver(): VerimorDriver
    {
        $config = $this->driverConfig('verimor');

        return new VerimorDriver($config, $this->httpClient($config));
    }

    protected function createNetgsmDriver(): NetgsmDriver
    {
        $config = $this->driverConfig('netgsm');

        return new NetgsmDriver($config, $this->httpClient($config));
    }

    protected function createVatansmsDriver(): VatanSmsDriver
    {
        $config = $this->driverConfig('vatansms');

        return new VatanSmsDriver($config, $this->httpClient($config));
    }

    protected function createIletimerkeziDriver(): IletiMerkeziDriver
    {
        $config = $this->driverConfig('iletimerkezi');

        return new IletiMerkeziDriver($config, $this->httpClient($config));
    }
}
