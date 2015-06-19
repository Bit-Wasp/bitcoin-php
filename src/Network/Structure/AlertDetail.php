<?php

namespace BitWasp\Bitcoin\Network\Structure;

use BitWasp\Bitcoin\Serializer\Network\Structure\AlertDetailSerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\SerializableInterface;

class AlertDetail extends Serializable
{
    /**
     * @var int
     */
    private $version;

    /**
     * Timestamp
     * @var int
     */
    private $relayUntil;

    /**
     * timestamp
     * @var int
     */
    private $expiration;

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $cancel;

    /**
     * @var integer[]
     */
    private $setCancel;

    /**
     * @var int
     */
    private $minVer;

    /**
     * @var int
     */
    private $maxVer;

    /**
     * @var integer[]
     */
    private $setSubVer;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var Buffer
     */
    private $comment;

    /**
     * @var Buffer
     */
    private $statusBar;

    /**
     * @param int $version
     * @param int $relayUntil
     * @param int $expiration
     * @param int $id
     * @param int $cancel
     * @param int $minVer
     * @param int $maxVer
     * @param int $priority
     * @param Buffer $comment
     * @param Buffer $statusBar
     * @param integer[] $setCancel
     * @param SerializableInterface[] $setSubVer
     */
    public function __construct(
        $version,
        $relayUntil,
        $expiration,
        $id,
        $cancel,
        $minVer,
        $maxVer,
        $priority,
        Buffer $comment,
        Buffer $statusBar,
        array $setCancel = [],
        array $setSubVer = []
    ) {
        $this->version = $version;
        $this->relayUntil = $relayUntil;
        $this->expiration = $expiration;
        $this->id = $id;
        $this->cancel = $cancel;
        $this->minVer = $minVer;
        $this->maxVer = $maxVer;
        $this->priority = $priority;
        $this->comment = $comment;
        $this->statusBar = $statusBar;
        $this->setCancel = $setCancel;
        $this->setSubVer = $setSubVer;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return int
     */
    public function getRelayUntil()
    {
        return $this->relayUntil;
    }

    /**
     * @return int
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCancel()
    {
        return $this->cancel;
    }

    /**
     * @return int
     */
    public function getMinVer()
    {
        return $this->minVer;
    }

    /**
     * @return int
     */
    public function getMaxVer()
    {
        return $this->maxVer;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return Buffer
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @return Buffer
     */
    public function getStatusBar()
    {
        return $this->statusBar;
    }

    /**
     * @return integer[]
     */
    public function getSetCancel()
    {
        return $this->setCancel;
    }

    /**
     * @return integer[]
     */
    public function getSetSubVer()
    {
        return $this->setSubVer;
    }
    /**
     * @see \BitWasp\Bitcoin\SerializableInterface::getBuffer()
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new AlertDetailSerializer())->serialize($this);
    }
}
