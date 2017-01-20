<?php

namespace BitWasp\Bitcoin\Serializer\Script;

use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Template;

class ScriptWitnessSerializer
{
    /**
     * @var Template
     */
    private $varstring;

    public function __construct()
    {
        $this->varstring = Types::varstring();
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
            $data = $this->varstring->parse($parser);
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
        $parser->appendBuffer(Buffertools::numToVarInt($witness->count()));
        foreach ($witness as $value) {
            $parser->appendBuffer(new Buffer($this->varstring->write($value)));
        }

        return $parser->getBuffer();
    }
}
