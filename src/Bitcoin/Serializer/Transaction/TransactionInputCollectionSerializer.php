<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\TransactionInputCollection;

class TransactionInputCollectionSerializer
{
    /**
     * @var TransactionInputSerializer
     */
    private $inputSerializer;

    /**
     * @param TransactionInputSerializer $inputSerializer
     */
    public function __construct(TransactionInputSerializer $inputSerializer)
    {
        $this->inputSerializer = $inputSerializer;
    }

    /**
     * @param TransactionInputCollection $inputs
     * @return array
     */
    public function serialize(TransactionInputCollection $inputs)
    {
        $inputArray = array();

        foreach ($inputs->getInputs() as $input) {
            $inputArray[] = $this->inputSerializer->serialize($input);
        }

        return $inputArray;
    }

    /**
     * @param Parser $parser
     * @return TransactionInputCollection
     */
    public function fromParser(Parser & $parser)
    {
        $inputs = new TransactionInputCollection;
        $inputs->addInputs(
            $parser->getArray(
                function () use (&$parser) {
                    $input = $this->inputSerializer->fromParser($parser);
                    return $input;
                }
            )
        );

        return $inputs;
    }

    /**
     * @param $string
     * @return TransactionInputCollection
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        $inputs = $this->fromParser($parser);
        return $inputs;
    }
}
