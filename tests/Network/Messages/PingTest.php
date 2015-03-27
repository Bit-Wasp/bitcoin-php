<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 27/03/15
 * Time: 01:15
 */

namespace BitWasp\Bitcoin\Test\Network\Messages;

use BitWasp\Bitcoin\Network\Messages\Ping;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class PingTest extends AbstractTestCase
{
    public function testPing()
    {
        $ping = new Ping();
        $this->assertInstanceOf('BitWasp\Bitcoin\Buffer', $ping->getNonce());
        $this->assertEquals('ping', $ping->getNetworkCommand());
        $this->assertInstanceOf('BitWasp\Bitcoin\Network\Messages\Pong', $ping->reply());

    }
}