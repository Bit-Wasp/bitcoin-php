<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\Collection\Transaction\TransactionWitnessCollection;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\ByteOrder;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\TemplateFactory;
use BitWasp\Buffertools\Types\Int32;
use BitWasp\Buffertools\Types\Uint32;
use BitWasp\Buffertools\Types\Uint8;
use BitWasp\Buffertools\Types\VarInt;
use BitWasp\Buffertools\Types\VarString;
use BitWasp\Buffertools\Types\Vector;

class WitnessTransactionSerializer
{
    /**
     * @var TransactionInputSerializer
     */
    public $inputSerializer;

    /**
     * @var TransactionOutputSerializer
     */
    public $outputSerializer;

    /**
     *
     */
    public function __construct()
    {
        $this->inputSerializer = new TransactionInputSerializer(new OutPointSerializer());
        $this->outputSerializer = new TransactionOutputSerializer;
        $this->witnessSerializer = new ScriptWitnessSerializer();
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getTemplate()
    {
        return (new TemplateFactory())
            ->int32le()
            ->int8()
            ->int8()
            ->vector(function (Parser & $parser) {
                return $this->inputSerializer->fromParser($parser);
            })
            ->vector(function (Parser &$parser) {
                return $this->outputSerializer->fromParser($parser);
            })
            ->vector(function (Parser $parser) {
                return $this->witnessSerializer->fromParser($parser);
            })
            ->uint32le()
            ->getTemplate();
    }

    public function s(TransactionInterface $transaction)
    {
        return $this->getTemplate()->write([
            $transaction->getVersion(),
            0,
            1,
            $transaction->getInputs()->all(),
            $transaction->getOutputs()->all(),
            $transaction->getWitnesses()->all(),
            $transaction->getLockTime()
        ]);
    }

    /**
     * @param TransactionInterface $transaction
     * @return BufferInterface
     */
    public function serialize(TransactionInterface $transaction)
    {
        $math = Bitcoin::getMath();
        $int32le = new Int32($math, ByteOrder::LE);
        $uint32le = new Uint32($math, ByteOrder::LE);

        $varint = new VarInt($math, ByteOrder::LE);
        $inputsSerializer = new Vector($varint, function (Parser $parser) {
            return $this->inputSerializer->fromParser($parser);
        });
        $flagsSerializer = new Uint8($math, ByteOrder::BE);
        $outputsSerializer = new Vector($varint, function (Parser $parser) {
            return $this->outputSerializer->fromParser($parser);
        });
        $witnessSerializer = new Vector($varint, function (Parser $parser) {
            return $this->witnessSerializer->fromParser($parser);
        });

        $binary = $int32le->write($transaction->getVersion());
        $flags = 0;
        if (!$transaction->getWitnesses()->isNull()) {
            $flags |= 1;
        }

        if ($flags) {
            // Dummy
            $binary .= $flagsSerializer->write(0);
            $binary .= $flagsSerializer->write($flags);
        }

        $binary .= $inputsSerializer->write($transaction->getInputs()->all());
        $binary .= $outputsSerializer->write($transaction->getOutputs()->all());

        if ($flags & 1) {
            $w = array_map(function (ScriptWitnessInterface $scriptWitness) {
                return $this->witnessSerializer->serialize($scriptWitness);
            }, $transaction->getWitnesses()->all());
            $binary .= $witnessSerializer->write($w);
        }

        $binary .= $uint32le->write($transaction->getLockTime());

        return new Buffer($binary, null, $math);
    }

    /**
     * @param Parser $parser
     * @return Transaction
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     * @throws \Exception
     */
    public function fromParser(Parser $parser)
    {
        $math = Bitcoin::getMath();
        $int32le = new Int32($math, ByteOrder::LE);
        $uint32le = new Uint32($math, ByteOrder::LE);

        $varint = new VarInt($math, ByteOrder::LE);
        $inputsSerializer = new Vector($varint, function (Parser $parser) {
            return $this->inputSerializer->fromParser($parser);
        });
        $flagsSerializer = new Uint8($math, ByteOrder::BE);
        $outputsSerializer = new Vector($varint, function (Parser $parser) {
            return $this->outputSerializer->fromParser($parser);
        });

        $vcharSerializer = new VarString($varint);
        $witnessSerializer = new Vector($varint, function (Parser $parser) use ($vcharSerializer) {
            return $vcharSerializer->read($parser);
        });

        $version = $int32le->read($parser);
        $dummy = (int) $flagsSerializer->read($parser);
        if ($dummy !== 0) {
            throw new \RuntimeException('Non-zero dummy in witness-bearing transaction');
        }

        $flags = (int) $flagsSerializer->read($parser);
        if ($math->cmp($flags, 0) != 0) {
            $vInputs = $inputsSerializer->read($parser);
            $vOutputs = $outputsSerializer->read($parser);
        } else {
            throw new \RuntimeException('Unknown flag');
        }

        $vWitness = [];
        if ($flags & 1) {
            $flags ^= 1;
            $vWitness = $witnessSerializer->read($parser);
        }

        if ($flags) {
            throw new \RuntimeException('Unknown optional data');
        }

        $locktime = $uint32le->read($parser);

        return new Transaction(
            $version,
            new TransactionInputCollection($vInputs),
            new TransactionOutputCollection($vOutputs),
            new TransactionWitnessCollection($vWitness),
            $locktime
        );
    }

    /**
     * @param $hex
     * @return Transaction
     */
    public function parse($hex)
    {
        $parser = new Parser($hex);
        return $this->fromParser($parser);
    }
}
