<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\OutPointInterface;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class OutPointSerializer implements OutPointSerializerInterface
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->bytestringle(32)
            ->uint32le()
            ->getTemplate();
    }

    /**
     * @param OutPointInterface $outpoint
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function serialize(OutPointInterface $outpoint)
    {
        return $this->getTemplate()->write([
            $outpoint->getTxId(),
            $outpoint->getVout()
        ]);
    }

    /**
     * @param Parser $parser
     * @return OutPointInterface
     */
    public function fromParser(Parser $parser)
    {
        list ($txid, $vout) = $this->getTemplate()->parse($parser);

        return new OutPoint($txid, $vout);
    }

    /**
     * @param string|\BitWasp\Buffertools\BufferInterface $data
     * @return OutPointInterface
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }
}
