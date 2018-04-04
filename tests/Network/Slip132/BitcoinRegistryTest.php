<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\Deterministic\HdPrefix\Slip132;

use BitWasp\Bitcoin\Network\Networks\Bitcoin;
use BitWasp\Bitcoin\Network\Slip132\BitcoinRegistry;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class BitcoinRegistryTest extends AbstractTestCase
{
    /**
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter
     * @throws \BitWasp\Bitcoin\Exceptions\MissingBip32Prefix
     */
    public function testXpubP2pkh()
    {
        $network = new Bitcoin();
        $registry = new BitcoinRegistry();
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
        $network = new Bitcoin();
        $registry = new BitcoinRegistry();
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
        $registry = new BitcoinRegistry();
        list ($priv, $pub) = $registry->getPrefixes(ScriptType::P2SH . "|" . ScriptType::P2WKH);

        $this->assertEquals("049d7cb2", $pub);
        $this->assertEquals("049d7878", $priv);
    }

    public function testYpubP2shP2wshP2pkh()
    {
        $registry = new BitcoinRegistry();
        list ($priv, $pub) = $registry->getPrefixes(ScriptType::P2SH . "|" . ScriptType::P2WSH . "|" . ScriptType::P2PKH);

        $this->assertEquals("0295b43f", $pub);
        $this->assertEquals("0295b005", $priv);
    }

    public function testzpubP2wpkh()
    {
        $registry = new BitcoinRegistry();
        list ($priv, $pub) = $registry->getPrefixes(ScriptType::P2WKH);

        $this->assertEquals("04b24746", $pub);
        $this->assertEquals("04b2430c", $priv);
    }

    public function testZpubP2wshP2pkh()
    {
        $registry = new BitcoinRegistry();
        list ($priv, $pub) = $registry->getPrefixes(ScriptType::P2WSH . "|" . ScriptType::P2PKH);

        $this->assertEquals("02aa7ed3", $pub);
        $this->assertEquals("02aa7a99", $priv);
    }
}
