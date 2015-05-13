<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;

use BitWasp\Bitcoin\Address\AddressFactory;

class TransactionOutput extends Serializable implements TransactionOutputInterface
{

    /**
     * @var string|int
     */
    private $value;

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * Initialize class
     *
     * @param int|string $value
     * @param ScriptInterface $script
     */
    public function __construct($value, ScriptInterface $script)
    {
        $this->value = $value;
        $this->script = $script;
    }

    /**
     * {@inheritdoc}
     * @see TransactionOutputInterface::getValue()
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     * @see TransactionOutputInterface::getScript()
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * {@inheritdoc}
     * @see TransactionOutputInterface::setScript()
     */
    public function setScript(ScriptInterface $script)
    {
        $this->script = $script;
        return $this;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        $serializer = new TransactionOutputSerializer();
        $out = $serializer->serialize($this);
        return $out;
    }

    /**
     * @param NetworkInterface|null $network
     * @return Address
     */
    public function getAddress(NetworkInterface $network = null)
    {
      $address = AddressFactory::fromOutputScript($this->getScript());
      return $address;
    }

    /**
     * @param NetworkInterface|null $network
     * @return string
     */
    public function getAddressString(NetworkInterface $network = null)
    {
      return $this->getAddress($network)->getAddress();
    }

}
