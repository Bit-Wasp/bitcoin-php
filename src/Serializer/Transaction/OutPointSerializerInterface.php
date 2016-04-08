<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Transaction\OutPointInterface;
use BitWasp\Buffertools\Parser;

interface OutPointSerializerInterface
{
    /**
     * @param OutPointInterface $outpoint
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function serialize(OutPointInterface $outpoint);

    /**
     * @param Parser $parser
     * @return OutPointInterface
     */
    public function fromParser(Parser $parser);

    /**
     * @param string|\BitWasp\Buffertools\BufferInterface $data
     * @return OutPointInterface
     */
    public function parse($data);
}
