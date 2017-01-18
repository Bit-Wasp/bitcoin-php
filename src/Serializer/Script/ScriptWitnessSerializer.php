<?php

namespace BitWasp\Bitcoin\Serializer\Script;

use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Template;
use BitWasp\Buffertools\TemplateFactory;

class ScriptWitnessSerializer
{
    private $template;

    public function __construct()
    {
        $this->template = new Template([
            Types::varstring()
        ]);
    }

    /**
     * @param Parser $parser
     * @param $size
     * @return ScriptWitness
     */
    public function fromParser(Parser $parser, $size)
    {
        $entries = [];
        for ($j = 0; $j < $size; $j++) {
            list ($data) = $this->template->parse($parser);
            $entries[] = $data;
        }

        return new ScriptWitness($entries);
    }

    /**
     * @param ScriptWitnessInterface $witness
     * @return BufferInterface
     */
    public function serialize(ScriptWitnessInterface $witness)
    {
        $parser = new Parser();
        $size = Buffertools::numToVarInt($witness->count());
        $parser->writeBuffer($size->getSize(), $size);
        foreach ($witness as $value) {
            $serialized = $this->template->write([$value]);
            $parser->writeBytes($serialized->getSize(), $serialized);
        }

        return $parser->getBuffer();
    }
}
