<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\Deterministic\HdPrefix\Slip132;

use BitWasp\Bitcoin\Network\Networks\BitcoinTestnet;
use BitWasp\Bitcoin\Network\Slip132\BitcoinTestnetRegistry;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class BitcoinTestnetRegistryTest extends AbstractTestCase
{
    /**
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter
     * @throws \BitWasp\Bitcoin\Exceptions\MissingBip32Prefix
     */
    public function testXpubP2pkh()
    {
        $network = new BitcoinTestnet();
        $registry = new BitcoinTestnetRegistry();
        list ($priv, $pub) = $registry->getPrefixes(ScriptType::P2PKH);

        $this->assertEquals(
            $network->getHDPubByte(),
            $pub
        );

        $this->assertEquals(
            $network->getHDPrivByte(),
            $priv
        );
    }
    /**
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter
     * @throws \BitWasp\Bitcoin\Exceptions\MissingBip32Prefix
     */
    public function testXpubP2shP2pkh()
    {
        $network = new BitcoinTestnet();
        $registry = new BitcoinTestnetRegistry();
        list ($priv, $pub) = $registry->getPrefixes(ScriptType::P2SH . "|" . ScriptType::P2PKH);

        $this->assertEquals(
            $network->getHDPubByte(),
            $pub
        );

        $this->assertEquals(
            $network->getHDPrivByte(),
            $priv
        );
    }

    public function testypubP2shP2wpkh()
    {
        $registry = new BitcoinTestnetRegistry();
        list ($priv, $pub) = $registry->getPrefixes(ScriptType::P2SH . "|" . ScriptType::P2WKH);

        $this->assertEquals("044a5262", $pub);
        $this->assertEquals("044a4e28", $priv);
    }

    public function testYpubP2shP2wshP2pkh()
    {
        $this->expectExceptionMessage("Unknown script type");
        $this->expectException(\InvalidArgumentException::class);

        $registry = new BitcoinTestnetRegistry();
        $registry->getPrefixes(ScriptType::P2SH . "|" . ScriptType::P2WSH . "|" . ScriptType::P2PKH);
    }

    public function testzpubP2wpkh()
    {
        $registry = new BitcoinTestnetRegistry();
        list ($priv, $pub) = $registry->getPrefixes(ScriptType::P2WKH);

        $this->assertEquals("045f1cf6", $pub);
        $this->assertEquals("045f18bc", $priv);
    }

    public function testZpubP2shP2wshP2pkh()
    {
        $registry = new BitcoinTestnetRegistry();
        list ($priv, $pub) = $registry->getPrefixes(ScriptType::P2WSH . "|" . ScriptType::P2PKH);

        $this->assertEquals("02575483", $pub);
        $this->assertEquals("02575048", $priv);
    }
}
