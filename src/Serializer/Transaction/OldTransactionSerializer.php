<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\TemplateFactory;

class OldTransactionSerializer
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
            ->int32le()
            ->vector(function (Parser $parser) {
                return $this->inputSerializer->fromParser($parser);
            })
            ->vector(function (Parser $parser) {
                return $this->outputSerializer->fromParser($parser);
            })
            ->uint32le()
            ->getTemplate();
    }

    /**
     * @param TransactionInterface $transaction
     * @return BufferInterface
     */
    public function serialize(TransactionInterface $transaction)
    {
        return $this->getTemplate()->write([
            $transaction->getVersion(),
            $transaction->getInputs(),
            $transaction->getOutputs(),
            $transaction->getLockTime()
        ]);
    }

    /**
     * @param Parser $parser
     * @return TransactionInterface
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     * @throws \Exception
     */
    public function fromParser(Parser $parser)
    {
        list ($nVersion, $inputArray, $outputArray, $nLockTime) = $this->getTemplate()->parse($parser);

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
        return $this->fromParser(new Parser($hex));
    }
}
