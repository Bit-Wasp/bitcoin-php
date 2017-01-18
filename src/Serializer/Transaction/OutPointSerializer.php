<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\OutPointInterface;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Template;

class OutPointSerializer implements OutPointSerializerInterface
{
    /**
     * @var \BitWasp\Buffertools\Template
     */
    private $template;

    public function __construct()
    {
        $this->template = $this->getTemplate();
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return new Template([
            Types::bytestringle(32),
            Types::uint32le()
        ]);
    }

    /**
     * @param OutPointInterface $outpoint
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function serialize(OutPointInterface $outpoint)
    {
        return $this->template->write([
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
        list ($txid, $vout) = $this->template->parse($parser);

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
