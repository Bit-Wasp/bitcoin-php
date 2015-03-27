<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Parser;

use BitWasp\Bitcoin\Transaction\TransactionOutputCollection;

class TransactionOutputCollectionSerializer
{
    /**
     * @var TransactionOutputSerializer
     */
    private $outputSerializer;

    /**
     * @param TransactionOutputSerializer $outputSerializer
     */
    public function __construct(TransactionOutputSerializer $outputSerializer)
    {
        $this->outputSerializer = $outputSerializer;
    }

    /**
     * @param TransactionOutputCollection $outputs
     * @return array
     */
    public function serialize(TransactionOutputCollection $outputs)
    {
        $outputArray = array();
        foreach ($outputs->getOutputs() as $output) {
            $outputArray[] = $this->outputSerializer->serialize($output);
        }

        return $outputArray;
    }

    /**
     * @param Parser $parser
     * @return TransactionOutputCollection
     */
    public function fromParser(Parser & $parser)
    {
        $outputs = new TransactionOutputCollection;
        $outputs->addOutputs(
            $parser->getArray(
                function () use (&$parser) {
                    return $this->outputSerializer->fromParser($parser);
                }
            )
        );

        return $outputs;
    }

    /**
     * @param $string
     * @return TransactionOutputCollection
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        $outputs = $this->fromParser($parser);
        return $outputs;
    }
}
