<?php

namespace Afk11\Bitcoin\Address;


interface AddressInterface
{
    /**
     * @return string
     */
    public function getPrefixByte();

    /**
     * @return string
     */
    public function getAddress();

    /**
     * @return string
     */
    public function getHash();
}