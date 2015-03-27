<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 27/03/15
 * Time: 01:17
 */

namespace BitWasp\Bitcoin\Test\Network\Messages;


use BitWasp\Bitcoin\Network\Messages\Ping;
use BitWasp\Bitcoin\Network\Messages\Pong;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class PongTest extends AbstractTestCase
{
    public function testPong()
    {
        $ping = new Ping();
        $pong = new Pong($ping);
        $this->assertEquals('pong', $pong->getNetworkCommand());
        $this->assertTrue($ping->getNonce() == $pong->getNonce());
    }

}