<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Bloom;

use BitWasp\Bitcoin\Amount;
use BitWasp\Bitcoin\Bloom\BloomFilter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Serializer\Bloom\BloomFilterSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class BloomFilterTest extends AbstractTestCase
{
    /**
     * @var PublicKeyFactory
     */
    private $pubKeyFactory;

    public function setUp()
    {
        $this->pubKeyFactory = new PublicKeyFactory();
        parent::setUp();
    }

    /**
     * @param BufferInterface $hex
     * @return BloomFilter
     */
    private function parseFilter(BufferInterface $hex)
    {
        return (new BloomFilterSerializer)->parse($hex);
    }

    /**
     * @return BloomFilter
     * @throws \Exception
     */
    private function getEmptyFilterVector()
    {
        return $this->parseFilter(Buffer::hex('2200000000000000000000000000000000000000000000000000000000000000000000120000000000000001'));
    }

    /**
     * @return BloomFilter
     * @throws \Exception
     */
    private function getFullFilterVector()
    {
        return $this->parseFilter(Buffer::hex('22FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF120000000000000001'));
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return \BitWasp\Bitcoin\Transaction\TransactionInterface
     */
    private function getPayToPubkeyTxVector(PublicKeyInterface $publicKey)
    {
        return TransactionFactory::build()
            ->input('0000000000000000000000000000000000000000000000000000000000000000', 0)
            ->output(50 * Amount::COIN, ScriptFactory::scriptPubKey()->payToPubKey($publicKey))
            ->get();
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return \BitWasp\Bitcoin\Transaction\TransactionInterface
     */
    private function getPayToMultisigTxVector(PublicKeyInterface $publicKey)
    {
        return TransactionFactory::build()
            ->input('0000000000000000000000000000000000000000000000000000000000000000', 0)
            ->output(50 * Amount::COIN, ScriptFactory::scriptPubKey()->multisig(1, [$publicKey]))
            ->get();
    }

    public function testBasics()
    {
        $math = new Math();
        $flags = BloomFilter::UPDATE_ALL;
        $filter = BloomFilter::create($math, 3, 0.01, 0, $flags);

        $buff = [
            Buffer::hex('99108ad8ed9bb6274d3980bab5a85c048f0950c8'),
            Buffer::hex('b5a2c786d9ef4658287ced5914b37a1b4aa32eee'),
            Buffer::hex('b9300670b4c5366e95b2699e8b18bc75e5f729c5')
        ];

        $bytes = Buffer::hex('a9030f7dbeb53a6ec0c2a1908b18b4c5eb67c2c1');

        foreach ($buff as $buf) {
            $filter->insertData($buf);
            $this->assertTrue($filter->containsData($buf));
            $this->assertFalse($filter->containsData($bytes));
        }

        $this->assertEquals('03614e9b050000000000000001', $filter->getBuffer()->getHex());
    }

    public function testEmptyContains()
    {
        $math = new Math();
        $flags = BloomFilter::UPDATE_ALL;
        $filter = BloomFilter::create($math, 3, 0.01, 0, $flags);
        $this->assertFalse($filter->containsData(new Buffer()));
    }

    public function testEmptyAcceptableSize()
    {
        $math = new Math();
        $flags = BloomFilter::UPDATE_ALL;
        $filter = BloomFilter::create($math, 3, 0.01, 0, $flags);
        $this->assertTrue($filter->hasAcceptableSize());
    }

    public function testEmptyRelevantAndUpdateTx()
    {
        $math = new Math();
        $flags = BloomFilter::UPDATE_ALL;
        $filter = BloomFilter::create($math, 3, 0.01, 0, $flags);
        $this->assertFalse($filter->isRelevantAndUpdate(new Transaction()));
    }

    public function testBasics2()
    {
        $math = new Math();
        $flags = BloomFilter::UPDATE_ALL;
        $filter = BloomFilter::create($math, 3, 0.01, 2147483649, $flags);

        $buff = [
            Buffer::hex('99108ad8ed9bb6274d3980bab5a85c048f0950c8'),
            Buffer::hex('b5a2c786d9ef4658287ced5914b37a1b4aa32eee'),
            Buffer::hex('b9300670b4c5366e95b2699e8b18bc75e5f729c5')
        ];

        $bytes = Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141');

        foreach ($buff as $buf) {
            $filter->insertData($buf);
            $this->assertTrue($filter->containsData($buf));
            $this->assertFalse($filter->containsData($bytes));
        }

        $this->assertEquals('03ce4299050000000100008001', $filter->getBuffer()->getHex());
        $parser = new BloomFilterSerializer();
        $parse = $parser->parse($filter->getBuffer());
        $this->assertEquals($filter, $parse);
    }

    public function testFlagChecks()
    {
        $math = new Math();
        $flagsAll = BloomFilter::UPDATE_ALL;
        $filter = BloomFilter::create($math, 3, 0.01, 2147483649, $flagsAll);
        $this->assertTrue($filter->isUpdateAll());
        $this->assertFalse($filter->isUpdateNone());
        $this->assertFalse($filter->isUpdatePubKeyOnly());

        $flagsNone = BloomFilter::UPDATE_NONE;
        $filter = BloomFilter::create($math, 3, 0.01, 2147483649, $flagsNone);
        $this->assertTrue($filter->isUpdateNone());
        $this->assertFalse($filter->isUpdatePubKeyOnly());
        $this->assertFalse($filter->isUpdateAll());

        $flagsP2P = BloomFilter::UPDATE_P2PUBKEY_ONLY;
        $filter = BloomFilter::create($math, 3, 0.01, 2147483649, $flagsP2P);
        $this->assertTrue($filter->isUpdatePubKeyOnly());
        $this->assertFalse($filter->isUpdateNone());
        $this->assertFalse($filter->isUpdateAll());
    }

    public function testForAFalsePositive()
    {
        /*
         * This test serves to ensure the behaviour of bloom filters.
         * 3 known values are inserted into the filter.
         * 2 values are checked against the filter - however these are obviously false positives.
         * 2 values are checked against the filter, which returns a definite no.
         */

        $math = new Math();
        $flags = BloomFilter::UPDATE_ALL;
        $filter = BloomFilter::create($math, 3, 0.01, 2147483649, $flags);

        foreach ([
                     Buffer::hex('99108ad8ed9bb6274d3980bab5a85c048f0950c8'),
                     Buffer::hex('b5a2c786d9ef4658287ced5914b37a1b4aa32eee'),
                     Buffer::hex('b9300670b4c5366e95b2699e8b18bc75e5f729c5')
                 ] as $buf) {
            $filter->insertData($buf);
            $this->assertTrue($filter->containsData($buf));
        }

        $falsePositives = [
            Buffer::hex('a408413bbc084c4875f73149052cc343aa00d0c913fe54d7f6d3821d432fceef'),
            Buffer::hex('f7ef30d3f2371e402a1533892155112fb14f783ac7d622e5f4648ad5b61161cf')
        ];

        foreach ($falsePositives as $buf) {
            $this->assertTrue($filter->containsData($buf));
        }

        $returnsNotFound = [
            Buffer::hex('4a1'),
            Buffer::hex('4190'),
        ];

        foreach ($returnsNotFound as $buf) {
            $this->assertFalse($filter->containsData($buf));
        }
    }

    public function testInsertKey()
    {
        $pub = $this->pubKeyFactory->fromHex('045b81f0017e2091e2edcd5eecf10d5bdd120a5514cb3ee65b8447ec18bfc4575c6d5bf415e54e03b1067934a0f0ba76b01c6b9ab227142ee1d543764b69d901e0');
        $math = new Math();
        $flags = BloomFilter::UPDATE_ALL;
        $filter = BloomFilter::create($math, 2, 0.001, 0, $flags);

        $filter->insertData($pub->getBuffer());
        $hash = $pub->getPubKeyHash();
        $filter->insertData($hash);

        $this->assertEquals('038fc16b080000000000000001', $filter->getBuffer()->getHex());
    }

    public function testEmptyFilterNeverMatches()
    {
        $pubkey = $this->pubKeyFactory->fromHex('045b81f0017e2091e2edcd5eecf10d5bdd120a5514cb3ee65b8447ec18bfc4575c6d5bf415e54e03b1067934a0f0ba76b01c6b9ab227142ee1d543764b69d901e0');
        $spends = $this->getPayToPubkeyTxVector($pubkey);

        $filter = $this->getEmptyFilterVector();
        $this->assertFalse($filter->isRelevantAndUpdate($spends));
    }

    public function testFullFilterAlwaysRelevant()
    {
        $pubkey = $this->pubKeyFactory->fromHex('045b81f0017e2091e2edcd5eecf10d5bdd120a5514cb3ee65b8447ec18bfc4575c6d5bf415e54e03b1067934a0f0ba76b01c6b9ab227142ee1d543764b69d901e0');
        $tx = $this->getPayToPubkeyTxVector($pubkey);
        $filter = $this->getFullFilterVector();
        $this->assertTrue($filter->isRelevantAndUpdate($tx));
    }

    public function testFullFilterAlwaysContainsData()
    {
        $filter = $this->getFullFilterVector();
        $this->assertTrue($filter->containsData(new Buffer('totally unrelated')));
    }

    public function testFullFilterNeverChanges()
    {
        $filter = $this->getFullFilterVector();
        $serialized = $filter->getBinary();

        $filter->insertData(new Buffer('new data'));

        $serialized2 = $filter->getBinary();
        $this->assertEquals($serialized, $serialized2);
    }

    public function testTxMatchesPayToPubkey()
    {
        $math = $this->safeMath();
        $pubkey = $this->pubKeyFactory->fromHex('045b81f0017e2091e2edcd5eecf10d5bdd120a5514cb3ee65b8447ec18bfc4575c6d5bf415e54e03b1067934a0f0ba76b01c6b9ab227142ee1d543764b69d901e0');

        $tx = $this->getPayToPubkeyTxVector($pubkey);

        $filter = BloomFilter::create($math, 10, 0.000001, 0, BloomFilter::UPDATE_P2PUBKEY_ONLY);
        $filter->insertData($pubkey->getBuffer());
        $this->assertTrue($filter->isRelevantAndUpdate($tx));
    }

    public function testTxMatchesPayToMultisig()
    {
        $math = $this->safeMath();
        $pubkey = $this->pubKeyFactory->fromHex('045b81f0017e2091e2edcd5eecf10d5bdd120a5514cb3ee65b8447ec18bfc4575c6d5bf415e54e03b1067934a0f0ba76b01c6b9ab227142ee1d543764b69d901e0');

        $tx = $this->getPayToMultisigTxVector($pubkey);

        $filter = BloomFilter::create($math, 10, 0.000001, 0, BloomFilter::UPDATE_P2PUBKEY_ONLY);
        $filter->insertData($pubkey->getBuffer());
        $this->assertTrue($filter->isRelevantAndUpdate($tx));
    }

    public function testTxMatches()
    {
        $math = new Math();
        $hex = '01000000010b26e9b7735eb6aabdf358bab62f9816a21ba9ebdb719d5299e88607d722c190000000008b4830450220070aca44506c5cef3a16ed519d7c3c39f8aab192c4e1c90d065f37b8a4af6141022100a8e160b856c2d43d27d8fba71e5aef6405b8643ac4cb7cb3c462aced7f14711a0141046d11fee51b0e60666d5049a9101a72741df480b96ee26488a4d3466b95c9a40ac5eeef87e10a5cd336c19a84565f80fa6c547957b7700ff4dfbdefe76036c339ffffffff021bff3d11000000001976a91404943fdd508053c75000106d3bc6e2754dbcff1988ac2f15de00000000001976a914a266436d2965547608b9e15d9032a7b9d64fa43188ac00000000';
        $tx = TransactionFactory::fromHex($hex);
        $spends = implode(
            '',
            array_map(
                function ($val) {
                    return str_pad(dechex($val), 2, '0', STR_PAD_LEFT);
                },
                [0x01, 0x00, 0x00, 0x00, 0x01, 0x6b, 0xff, 0x7f, 0xcd, 0x4f, 0x85, 0x65, 0xef, 0x40, 0x6d, 0xd5, 0xd6, 0x3d, 0x4f, 0xf9, 0x4f, 0x31, 0x8f, 0xe8, 0x20, 0x27, 0xfd, 0x4d, 0xc4, 0x51, 0xb0, 0x44, 0x74, 0x01, 0x9f, 0x74, 0xb4, 0x00, 0x00, 0x00, 0x00, 0x8c, 0x49, 0x30, 0x46, 0x02, 0x21, 0x00, 0xda, 0x0d, 0xc6, 0xae, 0xce, 0xfe, 0x1e, 0x06, 0xef, 0xdf, 0x05, 0x77, 0x37, 0x57, 0xde, 0xb1, 0x68, 0x82, 0x09, 0x30, 0xe3, 0xb0, 0xd0, 0x3f, 0x46, 0xf5, 0xfc, 0xf1, 0x50, 0xbf, 0x99, 0x0c, 0x02, 0x21, 0x00, 0xd2, 0x5b, 0x5c, 0x87, 0x04, 0x00, 0x76, 0xe4, 0xf2, 0x53, 0xf8, 0x26, 0x2e, 0x76, 0x3e, 0x2d, 0xd5, 0x1e, 0x7f, 0xf0, 0xbe, 0x15, 0x77, 0x27, 0xc4, 0xbc, 0x42, 0x80, 0x7f, 0x17, 0xbd, 0x39, 0x01, 0x41, 0x04, 0xe6, 0xc2, 0x6e, 0xf6, 0x7d, 0xc6, 0x10, 0xd2, 0xcd, 0x19, 0x24, 0x84, 0x78, 0x9a, 0x6c, 0xf9, 0xae, 0xa9, 0x93, 0x0b, 0x94, 0x4b, 0x7e, 0x2d, 0xb5, 0x34, 0x2b, 0x9d, 0x9e, 0x5b, 0x9f, 0xf7, 0x9a, 0xff, 0x9a, 0x2e, 0xe1, 0x97, 0x8d, 0xd7, 0xfd, 0x01, 0xdf, 0xc5, 0x22, 0xee, 0x02, 0x28, 0x3d, 0x3b, 0x06, 0xa9, 0xd0, 0x3a, 0xcf, 0x80, 0x96, 0x96, 0x8d, 0x7d, 0xbb, 0x0f, 0x91, 0x78, 0xff, 0xff, 0xff, 0xff, 0x02, 0x8b, 0xa7, 0x94, 0x0e, 0x00, 0x00, 0x00, 0x00, 0x19, 0x76, 0xa9, 0x14, 0xba, 0xde, 0xec, 0xfd, 0xef, 0x05, 0x07, 0x24, 0x7f, 0xc8, 0xf7, 0x42, 0x41, 0xd7, 0x3b, 0xc0, 0x39, 0x97, 0x2d, 0x7b, 0x88, 0xac, 0x40, 0x94, 0xa8, 0x02, 0x00, 0x00, 0x00, 0x00, 0x19, 0x76, 0xa9, 0x14, 0xc1, 0x09, 0x32, 0x48, 0x3f, 0xec, 0x93, 0xed, 0x51, 0xf5, 0xfe, 0x95, 0xe7, 0x25, 0x59, 0xf2, 0xcc, 0x70, 0x43, 0xf9, 0x88, 0xac, 0x00, 0x00, 0x00, 0x00, 0x00]
            )
        );
        $spendTx = TransactionFactory::fromHex($spends);

        $filter = BloomFilter::create($math, 10, 0.000001, 0, BloomFilter::UPDATE_ALL);
        $filter->insertData(Buffer::hex('b4749f017444b051c44dfd2720e88f314ff94f3dd6d56d40ef65854fcd7fff6b', 32));
        $this->assertTrue($filter->isRelevantAndUpdate($tx));

        $filter = BloomFilter::create($math, 10, 0.000001, 0, BloomFilter::UPDATE_ALL);
        $filter->insertData(Buffer::hex('b4749f017444b051c44dfd2720e88f314ff94f3dd6d56d40ef65854fcd7fff6b'));
        $this->assertTrue($filter->isRelevantAndUpdate($tx));

        $filter = BloomFilter::create($math, 10, 0.000001, 0, BloomFilter::UPDATE_ALL);
        $filter->insertData(Buffer::hex('30450220070aca44506c5cef3a16ed519d7c3c39f8aab192c4e1c90d065f37b8a4af6141022100a8e160b856c2d43d27d8fba71e5aef6405b8643ac4cb7cb3c462aced7f14711a01'));
        $this->assertTrue($filter->isRelevantAndUpdate($tx));

        $filter = BloomFilter::create($math, 10, 0.000001, 0, BloomFilter::UPDATE_ALL);
        $filter->insertData(Buffer::hex('046d11fee51b0e60666d5049a9101a72741df480b96ee26488a4d3466b95c9a40ac5eeef87e10a5cd336c19a84565f80fa6c547957b7700ff4dfbdefe76036c339'));
        $this->assertTrue($filter->isRelevantAndUpdate($tx));

        $filter = BloomFilter::create($math, 10, 0.000001, 0, BloomFilter::UPDATE_ALL);
        $filter->insertData(Buffer::hex('04943fdd508053c75000106d3bc6e2754dbcff19'));
        $this->assertTrue($filter->isRelevantAndUpdate($tx));
        $this->assertTrue($filter->isRelevantAndUpdate($spendTx));

        $filter = BloomFilter::create($math, 10, 0.000001, 0, BloomFilter::UPDATE_ALL);
        $filter->insertData(Buffer::hex('a266436d2965547608b9e15d9032a7b9d64fa431'));
        $this->assertTrue($filter->isRelevantAndUpdate($tx));

        $filter = BloomFilter::create($math, 10, 0.000001, 0, BloomFilter::UPDATE_ALL);
        $filter->insertOutPoint(new OutPoint(Buffer::hex('90c122d70786e899529d71dbeba91ba216982fb6ba58f3bdaab65e73b7e9260b'), 0));
        $this->assertTrue($filter->isRelevantAndUpdate($tx));

        $filter = BloomFilter::create($math, 10, 0.000001, 0, BloomFilter::UPDATE_ALL);
        $filter->insertData(Buffer::hex('0000006d2965547608b9e15d9032a7b9d64fa431'));
        $this->assertFalse($filter->isRelevantAndUpdate($tx));

        $filter = BloomFilter::create($math, 10, 0.000001, 0, BloomFilter::UPDATE_ALL);
        $filter->insertOutPoint(new OutPoint(Buffer::hex('41c1d247b5f6ef9952cd711beba91ba216982fb6ba58f3bdaab65e7341414141'), 0));
        $this->assertFalse($filter->isRelevantAndUpdate($tx));
    }

    public function testIsEmpty()
    {
        $emptyFilter = $this->getEmptyFilterVector();
        $this->assertFalse($emptyFilter->isFull());
        $this->assertTrue($emptyFilter->isEmpty());

        $fullFilter = $this->getFullFilterVector();
        $this->assertTrue($fullFilter->isFull());
        $this->assertFalse($fullFilter->isEmpty());
    }
}
