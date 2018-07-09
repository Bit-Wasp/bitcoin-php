<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\Deterministic\Slip132;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\Deterministic\Slip132\Slip132;
use BitWasp\Bitcoin\Network\Slip132\BitcoinRegistry;
use BitWasp\Bitcoin\Key\KeyToScript\KeyToScriptHelper;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class Slip132Test extends AbstractTestCase
{
    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter
     */
    public function testXpubP2pkh(EcAdapterInterface $adapter)
    {
        $slip132 = new Slip132(new KeyToScriptHelper($adapter));
        $registry = new BitcoinRegistry();
        $prefix = $slip132->p2pkh($registry);

        list ($priv, $pub) = $registry->getPrefixes($prefix->getScriptDataFactory()->getScriptType());
        $this->assertEquals($pub, $prefix->getPublicPrefix());
        $this->assertEquals($priv, $prefix->getPrivatePrefix());

        $factory = $prefix->getScriptDataFactory();
        $this->assertEquals(
            ScriptType::P2PKH,
            $factory->getScriptType()
        );
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     * @throws \BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter
     */
    public function testypubP2shP2wpkh(EcAdapterInterface $adapter)
    {
        $slip132 = new Slip132(new KeyToScriptHelper($adapter));
        $registry = new BitcoinRegistry();
        $prefix = $slip132->p2shP2wpkh($registry);

        list ($priv, $pub) = $registry->getPrefixes($prefix->getScriptDataFactory()->getScriptType());
        $this->assertEquals($pub, $prefix->getPublicPrefix());
        $this->assertEquals($priv, $prefix->getPrivatePrefix());

        $factory = $prefix->getScriptDataFactory();
        $this->assertEquals(
            ScriptType::P2SH . "|" . ScriptType::P2WKH,
            $factory->getScriptType()
        );
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     * @expectedException \BitWasp\Bitcoin\Exceptions\NotImplementedException
     * @expectedExceptionMessage Ypub/prv not supported yet
     */
    public function testYpubP2shP2wshP2pkh(EcAdapterInterface $adapter)
    {
        $slip132 = new Slip132(new KeyToScriptHelper($adapter));
        $registry = new BitcoinRegistry();
        $slip132->p2shP2wshP2pkh($registry);
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter
     */
    public function testzpubP2wpkh(EcAdapterInterface $adapter)
    {
        $slip132 = new Slip132(new KeyToScriptHelper($adapter));
        $registry = new BitcoinRegistry();
        $prefix = $slip132->p2wpkh($registry);

        list ($priv, $pub) = $registry->getPrefixes($prefix->getScriptDataFactory()->getScriptType());
        $this->assertEquals($pub, $prefix->getPublicPrefix());
        $this->assertEquals($priv, $prefix->getPrivatePrefix());

        $factory = $prefix->getScriptDataFactory();
        $this->assertEquals(
            ScriptType::P2WKH,
            $factory->getScriptType()
        );
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     * @expectedException \BitWasp\Bitcoin\Exceptions\NotImplementedException
     * @expectedExceptionMessage Zpub/prv not supported yet
     */
    public function testZpubP2shP2wshP2pkh(EcAdapterInterface $adapter)
    {
        $slip132 = new Slip132(new KeyToScriptHelper($adapter));
        $registry = new BitcoinRegistry();
        $slip132->p2wshP2pkh($registry);
    }
}
