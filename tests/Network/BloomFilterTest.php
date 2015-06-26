<?php

namespace BitWasp\Bitcoin\Tests;


use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\BloomFilter;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Buffertools\Buffer;

class BloomFilterTest extends AbstractTestCase
{
    public function testBasics()
    {
        $math = new Math();
        $flags = new Flags(BloomFilter::UPDATE_ALL);
        $filter = new BloomFilter($math, 3, 0.01, 0, $flags);

        $buff = [
            Buffer::hex('99108ad8ed9bb6274d3980bab5a85c048f0950c8'),
            Buffer::hex('b5a2c786d9ef4658287ced5914b37a1b4aa32eee'),
            Buffer::hex('b9300670b4c5366e95b2699e8b18bc75e5f729c5')
        ];

        $random = new Random();
        $bytes = $random->bytes(32);

        foreach ($buff as $buf) {
            $filter->insertData($buf);
            $this->assertTrue($filter->containsData($buf));
            $this->assertFalse($filter->containsData($bytes));
        }

        $this->assertEquals('03614e9b050000000000000001', $filter->getBuffer()->getHex());
    }

    public function testBasics2()
    {
        $math = new Math();
        $flags = new Flags(BloomFilter::UPDATE_ALL);
        $filter = new BloomFilter($math, 3, 0.01, 2147483649, $flags);

        $buff = [
            Buffer::hex('99108ad8ed9bb6274d3980bab5a85c048f0950c8'),
            Buffer::hex('b5a2c786d9ef4658287ced5914b37a1b4aa32eee'),
            Buffer::hex('b9300670b4c5366e95b2699e8b18bc75e5f729c5')
        ];

        $random = new Random();
        $bytes = $random->bytes(32);

        foreach ($buff as $buf) {
            $filter->insertData($buf);
            $this->assertTrue($filter->containsData($buf));
            $this->assertFalse($filter->containsData($bytes));
        }

        $this->assertEquals('03ce4299050000000100008001', $filter->getBuffer()->getHex());
    }

    public function testInsertKey()
    {
        $priv = PrivateKeyFactory::fromWif('5Kg1gnAjaLfKiwhhPpGS3QfRg2m6awQvaj98JCZBZQ5SuS2F15C');
        $pub = $priv->getPublicKey();
        $math = new Math();
        $flags = new Flags(BloomFilter::UPDATE_ALL);
        $filter = new BloomFilter($math, 2, 0.001, 0, $flags);

        $filter->insertData($pub->getBuffer());
        $hash = $pub->getPubKeyHash();
        $filter->insertData($hash);

        $this->assertEquals('038fc16b080000000000000001', $filter->getBuffer()->getHex());
    }

    public function testTxMatches()
    {
        $math = new Math();
        $hex = '01000000010b26e9b7735eb6aabdf358bab62f9816a21ba9ebdb719d5299e88607d722c190000000008b4830450220070aca44506c5cef3a16ed519d7c3c39f8aab192c4e1c90d065f37b8a4af6141022100a8e160b856c2d43d27d8fba71e5aef6405b8643ac4cb7cb3c462aced7f14711a0141046d11fee51b0e60666d5049a9101a72741df480b96ee26488a4d3466b95c9a40ac5eeef87e10a5cd336c19a84565f80fa6c547957b7700ff4dfbdefe76036c339ffffffff021bff3d11000000001976a91404943fdd508053c75000106d3bc6e2754dbcff1988ac2f15de00000000001976a914a266436d2965547608b9e15d9032a7b9d64fa43188ac00000000';
        $tx = TransactionFactory::fromHex($hex);

        $filter = new BloomFilter($math, 10, 0.000001, 0, new Flags(BloomFilter::UPDATE_ALL));
        $filter->insertHash('b4749f017444b051c44dfd2720e88f314ff94f3dd6d56d40ef65854fcd7fff6b');
        $this->assertTrue($filter->isRelevantAndUpdate($tx));

        $filter = new BloomFilter($math, 10, 0.000001, 0, new Flags(BloomFilter::UPDATE_ALL));
        $filter->insertData(Buffer::hex('30450220070aca44506c5cef3a16ed519d7c3c39f8aab192c4e1c90d065f37b8a4af6141022100a8e160b856c2d43d27d8fba71e5aef6405b8643ac4cb7cb3c462aced7f14711a01'));
        $this->assertTrue($filter->isRelevantAndUpdate($tx));

        $filter = new BloomFilter($math, 10, 0.000001, 0, new Flags(BloomFilter::UPDATE_ALL));
        $filter->insertOutpoint('90c122d70786e899529d71dbeba91ba216982fb6ba58f3bdaab65e73b7e9260b', '0');
        $this->assertTrue($filter->isRelevantAndUpdate($tx));

        $filter = new BloomFilter($math, 10, 0.000001, 0, new Flags(BloomFilter::UPDATE_ALL));
        $filter->insertData(Buffer::hex('a266436d2965547608b9e15d9032a7b9d64fa431'));
        $this->assertTrue($filter->isRelevantAndUpdate($tx));

        $filter = new BloomFilter($math, 10, 0.000001, 0, new Flags(BloomFilter::UPDATE_ALL));
        $filter->insertData(Buffer::hex('046d11fee51b0e60666d5049a9101a72741df480b96ee26488a4d3466b95c9a40ac5eeef87e10a5cd336c19a84565f80fa6c547957b7700ff4dfbdefe76036c339'));
        $this->assertTrue($filter->isRelevantAndUpdate($tx));


    }


}