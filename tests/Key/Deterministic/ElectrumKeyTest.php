<?php

namespace BitWasp\Bitcoin\Tests\Key\Deterministic;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Deterministic\ElectrumKey;
use BitWasp\Bitcoin\Key\Deterministic\ElectrumKeyFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ElectrumKeyTest extends AbstractTestCase
{

    /**
     * @return array
     */
    public function getTestKeys()
    {
        $vectors = [
            [
                'teach start paradise collect blade chill gay childhood creek picture creator branch',
                '74b1f6c0caae485b4aeb2f26bab3cabdec4f0b432751bd454fe11b2d2907cbda',
                '819519e966729f31e1855eb75133d9e7f0c31abaadd8f184870d62771c62c2e759406ace1dee933095d15e4c719617e252f32dc0465393055f867aee9357cd52',
                [
                    // sequence number => address derived at that sequence
                    [0, '15ZL6i899dDBXm8NoXwn7oup4J5yQJi1NH'],
                    [1, '1FQS2H5mcgh1btw9oxxZs3onfEdvwAPPiP'],
                    [2, '1CBYszxw91ArPx8jHCD3jH8P8VwzeQdC2Z'],
                    [3, '1N9qHajqjoMpY9FnWzwEAsorUcmbdAjA2F']
                ]
            ]
        ];

        $data = [];
        foreach ($vectors as $vector) {
            foreach ($this->getEcAdapters() as $adapter) {
                $data[] = [
                    $adapter[0],
                    $vector[0],
                    $vector[1],
                    $vector[2],
                    $vector[3],
                ];
            }
        }

        return $data;
    }

    /**
     * @dataProvider getTestKeys
     * @param EcAdapterInterface $ecAdapter
     * @param string $mnemonic
     * @param string $eSecExp
     * @param string $eMPK
     * @param array $eAddrList
     */
    public function testCKD(EcAdapterInterface $ecAdapter, $mnemonic, $eSecExp, $eMPK, array $eAddrList = array())
    {
        $keyPriv = ElectrumKeyFactory::fromMnemonic($mnemonic, null, $ecAdapter);
        $keyPub = new ElectrumKey($ecAdapter, $keyPriv->getMasterPublicKey());
        $this->assertEquals($eSecExp, $keyPriv->getMasterPrivateKey()->getHex());
        $this->assertEquals($eMPK, $keyPriv->getMPK()->getHex());

        foreach ($eAddrList as $vector) {
            list ($sequence, $eAddr) = $vector;
            $childPriv = $keyPriv->deriveChild($sequence);
            $this->assertEquals($eAddr, $childPriv->getAddress()->getAddress());

            $childPub = $keyPub->deriveChild($sequence);
            $this->assertEquals($eAddr, $childPub->getAddress()->getAddress());
        }
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Electrum keys are not compressed
     */
    public function testFromKey()
    {
        $key = PrivateKeyFactory::create(false);
        $e = ElectrumKeyFactory::fromKey($key);
        $this->assertInstanceOf('BitWasp\Bitcoin\Key\Deterministic\ElectrumKey', $e);

        $key = PrivateKeyFactory::create(true);
        ElectrumKeyFactory::fromKey($key);
    }

    public function testGenerate()
    {
        $random = new Random();
        $key = ElectrumKeyFactory::generateMasterKey($random->bytes(32));
        $this->assertInstanceOf('BitWasp\Bitcoin\Key\Deterministic\ElectrumKey', $key);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot produce master private key from master public key
     */
    public function testFailsWithoutMasterPrivateKey()
    {
        $key = PrivateKeyFactory::create()->getPublicKey();
        $e = ElectrumKeyFactory::fromKey($key);
        $e->getMasterPrivateKey();
    }
}
