<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Serializer\Script\ScriptWitnessSerializer;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;

class TransactionSerializer implements TransactionSerializerInterface
{
    /**
     * @var TransactionInputSerializer
     */
    private $inputSerializer;

    /**
     * @var TransactionOutputSerializer
     */
    private $outputSerializer;

    /**
     * @var ScriptWitnessSerializer
     */
    private $witnessSerializer;

    public function __construct(TransactionInputSerializer $txInSer = null, TransactionOutputSerializer $txOutSer = null, ScriptWitnessSerializer $witSer = null)
    {
        $this->inputSerializer = $txInSer ?: new TransactionInputSerializer(new OutPointSerializer());
        $this->outputSerializer = $txOutSer ?: new TransactionOutputSerializer;
        $this->witnessSerializer = $witSer ?: new ScriptWitnessSerializer();
    }

    /**
     * @param Parser $parser
     * @return TransactionInterface
     */
    public function fromParser(Parser $parser)
    {
        $int32le = Types::int32le();
        $uint32le = Types::uint32le();
        $varint = Types::varint();

        $version = $int32le->read($parser);

        $vin = [];
        $vinCount = $varint->read($parser);
        for ($i = 0; $i < $vinCount; $i++) {
            $vin[] = $this->inputSerializer->fromParser($parser);
        }

        $vout = [];
        $flags = 0;
        if (count($vin) === 0) {
            $flags = (int) $varint->read($parser);
            if ($flags !== 0) {
                $vinCount = $varint->read($parser);
                for ($i = 0; $i < $vinCount; $i++) {
                    $vin[] = $this->inputSerializer->fromParser($parser);
                }

                $voutCount = $varint->read($parser);
                for ($i = 0; $i < $voutCount; $i++) {
                    $vout[] = $this->outputSerializer->fromParser($parser);
                }
            }
        } else {
            $voutCount = $varint->read($parser);
            for ($i = 0; $i < $voutCount; $i++) {
                $vout[] = $this->outputSerializer->fromParser($parser);
            }
        }

        $vwit = [];
        if (($flags & 1)) {
            $flags ^= 1;
            $witCount = count($vin);
            for ($i = 0; $i < $witCount; $i++) {
                $vectorCount = $varint->read($parser);
                $vwit[] = $this->witnessSerializer->fromParser($parser, $vectorCount);
            }
        }

        if ($flags) {
            throw new \RuntimeException('Flags byte was 0');
        }

        $lockTime = $uint32le->read($parser);

        return new Transaction(
            $version,
            $vin,
            $vout,
            $vwit,
            $lockTime
        );
    }

    /**
     * @param string|BufferInterface $data
     * @return TransactionInterface
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param TransactionInterface $transaction
     * @return BufferInterface
     */
    public function serialize(TransactionInterface $transaction)
    {
        $int8le = Types::int8le();
        $int32le = Types::int32le();
        $uint32le = Types::uint32le();
        $flags = 0;

        $binary = $int32le->write($transaction->getVersion());
        if ($transaction->hasWitness()) {
            $flags |= 1;
        }

        if ($flags) {
            $binary .= $int8le->write(0);
            $binary .= $int8le->write($flags);
        }

        $parser = new Parser(new Buffer($binary));
        $parser->appendBuffer(Buffertools::numToVarInt(count($transaction->getInputs())), true);
        foreach ($transaction->getInputs() as $input) {
            $parser->appendBuffer($this->inputSerializer->serialize($input));
        }

        $parser->appendBuffer(Buffertools::numToVarInt(count($transaction->getOutputs())), true);
        foreach ($transaction->getOutputs() as $output) {
            $parser->appendBuffer($this->outputSerializer->serialize($output));
        }

        if ($flags & 1) {
            foreach ($transaction->getWitnesses() as $witness) {
                $parser->appendBuffer($this->witnessSerializer->serialize($witness));
            }
        }

        $parser->writeRawBinary(4, $uint32le->write($transaction->getLockTime()));

        return $parser->getBuffer();
    }
}
