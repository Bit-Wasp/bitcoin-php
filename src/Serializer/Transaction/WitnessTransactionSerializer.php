<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Buffertools\ByteOrder;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\TemplateFactory;
use BitWasp\Buffertools\Types\Uint32;
use BitWasp\Buffertools\Types\VarInt;
use BitWasp\Buffertools\Types\Vector;

class TransactionSerializer
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
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getTemplate()
    {
        return (new TemplateFactory())
            ->uint32le()
            ->vector(function (Parser & $parser) {
                return $this->inputSerializer->fromParser($parser);
            })
            ->vector(function (Parser &$parser) {
                return $this->outputSerializer->fromParser($parser);
            })
            ->uint32le()
            ->getTemplate();
    }

    /**
     * @param TransactionInterface $transaction
     * @return string
     */
    public function serialize(TransactionInterface $transaction)
    {
        return $this->getTemplate()->write([
            $transaction->getVersion(),
            $transaction->getInputs()->all(),
            $transaction->getOutputs()->all(),
            $transaction->getLockTime()
        ]);
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
        $uint32le = new Uint32($math, ByteOrder::LE);
        $version = $uint32le->read($parser);
        $inputsSerializer = new Vector(new VarInt($math, ByteOrder::BE), function (Parser $parser) {
            return $this->inputSerializer->fromParser($parser);
        });
        $outputsSerializer = new Vector(new VarInt($math, ByteOrder::BE), function (Parser $parser) {
            return $this->inputSerializer->fromParser($parser);
        });

        $p  = $this->getTemplate()->parse($parser);

        list ($nVersion, $inputArray, $outputArray, $nLockTime) = $p;

        return (new TxBuilder())
            ->version($nVersion)
            ->inputs($inputArray)
            ->outputs($outputArray)
            ->locktime($nLockTime)
            ->get();
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
