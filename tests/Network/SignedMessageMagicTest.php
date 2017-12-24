<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Network;

use BitWasp\Bitcoin\Network\Networks\Bitcoin;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class SignedMessageMagicTest extends AbstractTestCase
{
    public function testGetSignedMessageMagic()
    {
        $bitcoin = new Bitcoin();
        $this->assertEquals("Bitcoin Signed Message", $bitcoin->getSignedMessageMagic());
    }
}
