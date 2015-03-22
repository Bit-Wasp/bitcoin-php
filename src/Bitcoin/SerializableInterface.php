<?php

namespace BitWasp\Bitcoin;

interface SerializableInterface
{
    /**
     * @return Buffer
     */
    public function getBuffer();
}
