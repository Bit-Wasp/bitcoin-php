<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Script\Consensus\BitcoinConsensus;
use BitWasp\Bitcoin\Script\Consensus\Exception\BitcoinConsensusException;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;

class BitcoinConsensusTest extends AbstractTestCase
{
    public function testOptionalCheckScriptFlags()
    {
        if (extension_loaded('bitcoinconsensus')) {
            $flags = 1 | 3 | 2 | 65;
            $check = $flags == ($flags & BITCOINCONSENSUS_VERIFY_ALL);
            $this->assertFalse($check);

            $c = new BitcoinConsensus();
            $this->assertThrows(function () use ($c, $flags) {
                $c->verify(new Transaction(), new Script(null), $flags, 0, 0);
            }, BitcoinConsensusException::class, 'Invalid flags for bitcoinconsensus');
        }
    }
}
