<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Serializer\Script\ScriptWitnessSerializer;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\ByteOrder;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Types\Int32;
use BitWasp\Buffertools\Types\Int8;
use BitWasp\Buffertools\Types\Uint32;
use BitWasp\Buffertools\Types\VarInt;
use BitWasp\Buffertools\Types\Vector;

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
        $math = Bitcoin::getMath();
        $int32le = new Int32($math, ByteOrder::LE);
        $uint32le = new Uint32($math, ByteOrder::LE);
        $varint = new VarInt($math, ByteOrder::LE);

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
        $math = Bitcoin::getMath();
        $int8le = new Int8($math, ByteOrder::LE);
        $int32le = new Int32($math, ByteOrder::LE);
        $uint32le = new Uint32($math, ByteOrder::LE);
        $varint = new VarInt($math, ByteOrder::LE);
        $vector = new Vector($varint, function () {
        });

        $binary = $int32le->write($transaction->getVersion());
        $flags = 0;

        if (!empty($transaction->getWitnesses())) {
            $flags |= 1;
        }

        if ($flags) {
            $binary .= $int8le->write(0);
            $binary .= $int8le->write($flags);
        }

        $binary .= $vector->write($transaction->getInputs());
        $binary .= $vector->write($transaction->getOutputs());

        if ($flags & 1) {
            foreach ($transaction->getWitnesses() as $witness) {
                $binary .= $witness->getBuffer()->getBinary();
            }
        }

        $binary .= $uint32le->write($transaction->getLockTime());

        return new Buffer($binary);
    }
}
