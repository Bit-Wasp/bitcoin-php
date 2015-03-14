<?php

namespace Afk11\Bitcoin\Serializer\Transaction;

use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Script\Script;
use Afk11\Bitcoin\Transaction\TransactionInputCollection;

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
    public function fromParser(Parser &$parser)
    {
        $inputs = new TransactionInputCollection;
        $inputs->addInputs(
            $parser->getArray(
                function () use (&$parser) {
                    $input = new \Afk11\Bitcoin\Transaction\TransactionInput();
                    $input
                        ->setTransactionId($parser->readBytes(32, true)->serialize('hex'))
                        ->setVout($parser->readBytes(4)->serialize('int'))
                        ->setScript(new Script($parser->getVarString()))
                        ->setSequence($parser->readBytes(4)->serialize('int'));
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
