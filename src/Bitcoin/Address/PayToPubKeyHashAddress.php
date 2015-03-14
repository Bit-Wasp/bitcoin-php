<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 14/03/15
 * Time: 01:40
 */

namespace Afk11\Bitcoin\Address;


use Afk11\Bitcoin\Base58;
use Afk11\Bitcoin\Address\AddressInterface;
use Afk11\Bitcoin\NetworkInterface;
use Afk11\Bitcoin\Key\KeyInterface;

class PayToPubKeyHashAddress extends Address
{
    /**
     * @var NetworkInterface
     */
    private $network;

    /**
     * @var KeyInterface
     */
    private $key;

    /**
     * @param NetworkInterface $network
     * @param KeyInterface $key
     */
    public function __construct(NetworkInterface $network, KeyInterface $key)
    {
        $this->network = $network;
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getPrefixByte()
    {
        return $this->network->getAddressByte();
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->key->getPubKeyHash();
    }

}