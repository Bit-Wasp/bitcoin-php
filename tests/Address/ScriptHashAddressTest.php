<?php

namespace BitWasp\Bitcoin\Tests\Address;

use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class ScriptHashAddressTest extends AbstractTestCase
{
    public function testInvalidSize19()
    {
        $buffer = new Buffer('', 19);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("P2SH address hash should be 20 bytes");
        new ScriptHashAddress($buffer);
    }

    public function testInvalidSize21()
    {
        $buffer = new Buffer('', 21);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("P2SH address hash should be 20 bytes");
        new ScriptHashAddress($buffer);
    }
}
