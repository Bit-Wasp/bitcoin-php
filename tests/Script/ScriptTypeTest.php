<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ScriptTypeTest extends AbstractTestCase
{
    public function testConstants()
    {
        $this->assertEquals('pubkey', ScriptType::P2PK);
        $this->assertEquals('pubkey', OutputClassifier::P2PK);
        $this->assertEquals('pubkey', OutputClassifier::PAYTOPUBKEY);

        $this->assertEquals('pubkeyhash', ScriptType::P2PKH);
        $this->assertEquals('pubkeyhash', OutputClassifier::P2PKH);
        $this->assertEquals('pubkeyhash', OutputClassifier::PAYTOPUBKEYHASH);

        $this->assertEquals('multisig', ScriptType::MULTISIG);
        $this->assertEquals('multisig', OutputClassifier::MULTISIG);

        $this->assertEquals('scripthash', ScriptType::P2SH);
        $this->assertEquals('scripthash', OutputClassifier::P2SH);
        $this->assertEquals('scripthash', OutputClassifier::PAYTOSCRIPTHASH);

        $this->assertEquals('nulldata', ScriptType::NULLDATA);
        $this->assertEquals('nulldata', OutputClassifier::NULLDATA);

        $this->assertEquals('witness_v0_scripthash', ScriptType::P2WSH);
        $this->assertEquals('witness_v0_scripthash', OutputClassifier::P2WSH);
        $this->assertEquals('witness_v0_scripthash', OutputClassifier::WITNESS_V0_SCRIPTHASH);

        $this->assertEquals('witness_v0_keyhash', ScriptType::P2WKH);
        $this->assertEquals('witness_v0_keyhash', OutputClassifier::P2WKH);
        $this->assertEquals('witness_v0_keyhash', OutputClassifier::WITNESS_V0_KEYHASH);

        $this->assertEquals('witness_coinbase_commitment', ScriptType::WITNESS_COINBASE_COMMITMENT);
    }
}
