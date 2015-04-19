<?php

namespace BitWasp\Bitcoin;

use BitWasp\Buffertools\Buffer;

interface SerializableInterface extends \BitWasp\Buffertools\SerializableInterface
{
    /**
     * @return Buffer
     */
    public function getBuffer();

    /**
     * @return string
     */
    public function getHex();

    /**
     * @return string
     */
    public function getBinary();

    /**
     * @return string
     */
    public function getInt();
}
