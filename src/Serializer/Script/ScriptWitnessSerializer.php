<?php

namespace BitWasp\Bitcoin\Serializer\Script;

use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class ScriptWitnessSerializer
{

    public function fromParser(Parser $parser, $size)
    {
        $varstring = (new TemplateFactory())->varstring()->getTemplate();
        $entries = [];

        for ($j = 0; $j < $size; $j++) {
            list ($data) = $varstring->parse($parser);
            $entries[] = $data;
        }

        return new ScriptWitness($entries);
    }

    public function serialize(ScriptWitnessInterface $witness)
    {
        $varstring = (new TemplateFactory())->varstring()->getTemplate();
        $vector =  (new TemplateFactory())->vector(function () {
        })->getTemplate();

        $strs= [];
        foreach ($witness as $value) {
            $strs[] = $varstring->write([$value]);
        }
        return $vector->write([$strs]);
    }
}
