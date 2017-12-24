<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Network;

use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Network\Networks\Bitcoin;
use BitWasp\Bitcoin\Network\Networks\BitcoinRegtest;
use BitWasp\Bitcoin\Network\Networks\BitcoinTestnet;
use BitWasp\Bitcoin\Network\Networks\Dash;
use BitWasp\Bitcoin\Network\Networks\DashTestnet;
use BitWasp\Bitcoin\Network\Networks\Dogecoin;
use BitWasp\Bitcoin\Network\Networks\DogecoinTestnet;
use BitWasp\Bitcoin\Network\Networks\Litecoin;
use BitWasp\Bitcoin\Network\Networks\LitecoinTestnet;
use BitWasp\Bitcoin\Network\Networks\Viacoin;
use BitWasp\Bitcoin\Network\Networks\ViacoinTestnet;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class NetworkFactoryTest extends AbstractTestCase
{
    public function getFactoryMethodAndClass()
    {
        return [
            ['bitcoin', Bitcoin::class],
            ['bitcoinTestnet', BitcoinTestnet::class],
            ['bitcoinRegtest', BitcoinRegtest::class],
            ['dash', Dash::class],
            ['dashTestnet', DashTestnet::class],
            ['dogecoin', Dogecoin::class],
            ['dogecoinTestnet', DogecoinTestnet::class],
            ['litecoin', Litecoin::class],
            ['litecoinTestnet', LitecoinTestnet::class],
            ['viacoin', Viacoin::class],
            ['viacoinTestnet', ViacoinTestnet::class],
        ];
    }

    /**
     * @param $method
     * @param $expectedClass
     * @dataProvider getFactoryMethodAndClass
     */
    public function testNetworkFactory($method, $expectedClass)
    {
        $this->assertInstanceOf($expectedClass, call_user_func([NetworkFactory::class, $method]));
    }
}
