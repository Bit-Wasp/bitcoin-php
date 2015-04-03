<?php

namespace BitWasp\Bitcoin;

abstract class Serializable implements SerializableInterface
{
    /**
     * @return string
     */
    public function getHex()
    {
        return $this->getBuffer()->getHex();
    }

    /**
     * @return string
     */
    public function getBinary()
    {
        return $this->getBuffer()->getBinary();
    }

    /**
     * @return string
     */
    public function getInt()
    {
        return $this->getBuffer()->getInt();
    }
}
