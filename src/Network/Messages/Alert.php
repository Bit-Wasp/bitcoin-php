<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Network\Structure\AlertDetail;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Signature\SignatureInterface;

class Alert
{
    /**
     * @var AlertDetail
     */
    protected $alert;

    /**
     * @var SignatureInterface
     */
    protected $signature;

    /**
     * @param AlertDetail $alert
     * @param SignatureInterface $signature
     */
    public function __construct(AlertDetail $alert, SignatureInterface $signature)
    {
        $this->alert = $alert;
        $this->signature = $signature;
    }

    /**
     * @return AlertDetail
     */
    public function getAlert()
    {
        return $this->alert;
    }

    /**
     * @return SignatureInterface
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @see \BitWasp\Bitcoin\SerializableInterface::getBuffer()
     * @return \BitWasp\Buffertools\Buffer
     */
    public function getBuffer()
    {
        $parser = new Parser($this->alert->getBuffer()->getBinary() . $this->signature->getBuffer()->getBinary());
        return $parser->getBuffer();
    }
}
