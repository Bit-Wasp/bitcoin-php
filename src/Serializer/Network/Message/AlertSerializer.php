<?php

namespace BitWasp\Bitcoin\Serializer\Network\Message;

use BitWasp\Bitcoin\Network\Messages\Alert;
use BitWasp\Bitcoin\Network\Structure\AlertDetail;
use BitWasp\Bitcoin\Serializer\Network\Structure\AlertDetailSerializer;
use BitWasp\Bitcoin\Signature\Signature;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class AlertSerializer
{
    /**
     * @var AlertDetailSerializer
     */
    private $detail;

    /**
     * @param AlertDetailSerializer $detail
     */
    public function __construct(AlertDetailSerializer $detail)
    {
        $this->detail = $detail;
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getSigTemplate()
    {
        return (new TemplateFactory())
            ->uint256()
            ->uint256()
            ->getTemplate();
    }

    /**
     * @param Parser $parser
     * @return Alert
     */
    public function fromParser(Parser & $parser)
    {
        $detail = $this->detail->fromParser($parser);
        list ($sigR, $sigS) = $this->getSigTemplate()->parse($parser);
        return new Alert(
            $detail,
            new Signature($sigR, $sigS)
        );
    }

    /**
     * @param $data
     * @return Alert
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param Alert $alert
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(Alert $alert)
    {
        $sig = $alert->getSignature();
        return Buffertools::concat(
            $alert->getDetail()->getBuffer(),
            $this->getSigTemplate()->write([
                $sig->getR(),
                $sig->getS()
            ])
        );
    }
}
