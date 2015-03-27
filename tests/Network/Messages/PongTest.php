<?php

namespace BitWasp\Bitcoin\Test\Network\Messages;


use BitWasp\Bitcoin\Bitcoin;
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

        $math = Bitcoin::getMath();
        $this->assertEquals($math->decHex($ping->getNonce()), $pong->getBuffer()->getHex());
    }

}