<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\TransactionInputWitness;
use BitWasp\Bitcoin\Transaction\TransactionInputWitnessInterface;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class TransactionInputWitnessSerializer
{
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->varstring()
            ->getTemplate();
    }

    public function fromParser(Parser $parser)
    {
        list ($witness) = $this->getTemplate()->parse($parser);
        $script = new Script($witness);
        return new TransactionInputWitness(array_map(function (Operation $operation) {
            if (!$operation->isPush()) {
                throw new \RuntimeException('Non-push in TransactionInputWitness');
            }

            return $operation->getData();
        }, $script->getScriptParser()->decode()));

    }

    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    public function serialize()
}
