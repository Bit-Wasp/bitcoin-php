<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Collection\Transaction\TransactionWitnessCollection;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class TransactionWitnessSerializer
{

    public function fromParser(Parser $parser, $count)
    {
        $varint = (new TemplateFactory())->varint()->getTemplate();
        $varstring = (new TemplateFactory())->varstring()->getTemplate();

        $vWit = array_fill(0, $count, []);
        for ($i = 0; $i < $count; $i++) {
            $size = $varint->parse($parser);
            $entries = [];
            for ($j = 0; $j < $size; $j++) {
                $entries[] = $varstring->parse($parser);
            }

            $vWit[] = new ScriptWitness($entries);
        }

        return new TransactionWitnessCollection($vWit);
    }

    public function serialize(TransactionInterface $transaction)
    {
        $varint = (new TemplateFactory())->varint()->getTemplate();
        $varstring = (new TemplateFactory())->varstring()->getTemplate();

        $count = count($transaction->getInputs());
        $value = '';
        for ($i = 0; $i < $count; $i++) {
            $witness = $transaction->getWitness($i);
            $value .= $varint->write([count($witness)])->getBinary();
            foreach ($witness as $value) {
                $value .= $varstring->write([$value]);
            }
        }

        return new Buffer($value);
    }
}
