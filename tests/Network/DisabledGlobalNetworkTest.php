<?php

namespace BitWasp\Bitcoin\Tests\Network;

use BitWasp\Bitcoin\Network\DisabledGlobalNetwork;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class DisabledGlobalNetworkTest extends AbstractTestCase
{
    public function getMethods()
    {
        $methods = [];
        foreach (get_class_methods(NetworkInterface::class) as $method) {
            $methods[] = [$method];
        }
        return $methods;
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\DisabledGlobalNetworkException
     * @dataProvider getMethods
     * @param string $method
     */
    public function testThrows($method)
    {
        $network = new DisabledGlobalNetwork();
        $network->{$method}();
    }
}
