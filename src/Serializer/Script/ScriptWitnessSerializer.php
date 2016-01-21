<?php

namespace BitWasp\Bitcoin\Serializer\Script;

use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class ScriptWitnessSerializer
{

    public function fromParser(Parser $parser, $size)
    {
        echo "parsing script witness\n";
        $varstring = (new TemplateFactory())->varstring()->getTemplate();

        $entries = [];

        for ($j = 0; $j < $size; $j++) {
            echo "$j\n";
            list ($data) = $varstring->parse($parser);
            $entries[] = $data;
        }

        return new ScriptWitness($entries);
    }

    public function serialize(ScriptWitnessInterface $witness)
    {
        $varstring = (new TemplateFactory())->varstring()->getTemplate();

        $str = '';
        foreach ($witness as $value) {
            $str .= $varstring->write([$value])->getBinary();
        }

        return new Buffer($str);
    }
}
