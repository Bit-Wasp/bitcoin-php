<?php

namespace BitWasp\Bitcoin;

interface SerializableInterface
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

    /**
     * @return string
     */
    public function __toString();
}
