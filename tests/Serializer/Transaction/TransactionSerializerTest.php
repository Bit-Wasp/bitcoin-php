<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Serializer\Transaction;

use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Buffertools\Buffer;

class TransactionSerializerTest extends AbstractTestCase
{
    public function getTransactionSerializationFixtures()
    {
        $fixtures = json_decode($this->dataFile('signer_fixtures.json'), true);
        if (!$fixtures) {
            throw new \RuntimeException("bad tx serialization fixtures");
        }
        $vectors = [];
        foreach ($fixtures['valid'] as $vector) {
            if (array_key_exists('hex', $vector) && $vector['hex'] !== '') {
                $vectors[] = [TransactionSerializer::NO_WITNESS, $vector['hex']];
            }
            if (array_key_exists('whex', $vector)&& $vector['whex'] !== '') {
                $vectors[] = [0, $vector['whex']];
            }
        }
        return $vectors;
    }

    /**
     * @param int $flags
     * @param string $tx
     * @dataProvider getTransactionSerializationFixtures
     */
    public function testTransactionSerializer(int $flags, string $tx)
    {
        $serializer = new TransactionSerializer();
        $parsed = $serializer->parse(Buffer::hex($tx));
    
        $serialized = $serializer->serialize($parsed);
        $this->assertEquals($tx, $serialized->getHex());
    }

    public function testValidTxinVarint()
    {
        $hex = $this->dataFile("biginputtx.valid.txt");
        $tx = TransactionFactory::fromHex($hex);
        $this->assertEquals(300, count($tx->getInputs()));
        
        $serialized = $tx->getHex();
        $this->assertEquals($hex, $serialized);
    }

    /**
     * @expectedException \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     * @expectedExceptionMessage Insufficient data remaining for VarString
     */
    public function testInvalidTxinVarint()
    {
        // not perfect, but gotta explode somewhere
        $hex = $this->dataFile('biginputtx.invalid.txt');
        TransactionFactory::fromHex($hex);
    }
}
