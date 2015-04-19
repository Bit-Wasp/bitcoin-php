<?php

namespace BitWasp\Bitcoin\PaymentProtocol\Protobufs;

class PaymentACK extends \DrSlump\Protobuf\Message
{

    /**  @var \BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment */
    public $payment = null;

    /**  @var string */
    public $memo = null;


    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
        $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, 'payments.PaymentACK');

        // REQUIRED MESSAGE payment = 1
        $f = new \DrSlump\Protobuf\Field();
        $f->number = 1;
        $f->name = "payment";
        $f->type = \DrSlump\Protobuf::TYPE_MESSAGE;
        $f->rule = \DrSlump\Protobuf::RULE_REQUIRED;
        $f->reference = '\BitWasp\Bitcoin\Payments\Protobufs\Payment';
        $descriptor->addField($f);

        // OPTIONAL STRING memo = 2
        $f = new \DrSlump\Protobuf\Field();
        $f->number = 2;
        $f->name = "memo";
        $f->type = \DrSlump\Protobuf::TYPE_STRING;
        $f->rule = \DrSlump\Protobuf::RULE_OPTIONAL;
        $descriptor->addField($f);

        foreach (self::$__extensions as $cb) {
            $descriptor->addField($cb(), true);
        }

        return $descriptor;
    }

    /**
     * Check if <payment> has a value
     *
     * @return boolean
     */
    public function hasPayment()
    {
        return $this->_has(1);
    }

    /**
     * Clear <payment> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentACK
     */
    public function clearPayment()
    {
        return $this->_clear(1);
    }

    /**
     * Get <payment> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment
     */
    public function getPayment()
    {
        return $this->_get(1);
    }

    /**
     * Set <payment> value
     *
     * @param \BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentACK
     */
    public function setPayment(\BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment $value)
    {
        return $this->_set(1, $value);
    }

    /**
     * Check if <memo> has a value
     *
     * @return boolean
     */
    public function hasMemo()
    {
        return $this->_has(2);
    }

    /**
     * Clear <memo> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentACK
     */
    public function clearMemo()
    {
        return $this->_clear(2);
    }

    /**
     * Get <memo> value
     *
     * @return string
     */
    public function getMemo()
    {
        return $this->_get(2);
    }

    /**
     * Set <memo> value
     *
     * @param string $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentACK
     */
    public function setMemo($value)
    {
        return $this->_set(2, $value);
    }
}
