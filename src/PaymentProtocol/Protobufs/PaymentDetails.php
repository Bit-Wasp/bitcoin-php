<?php

namespace BitWasp\Bitcoin\PaymentProtocol\Protobufs;

use DrSlump\Protobuf;
use DrSlump\Protobuf\Descriptor;
use DrSlump\Protobuf\Field;
use \DrSlump\Protobuf\Message;

class PaymentDetails extends Message
{

    /**  @var string */
    public $network = "main";

    /**  @var \BitWasp\Bitcoin\PaymentProtocol\Protobufs\Output[] */
    public $outputs = array();

    /**  @var int */
    public $time = null;

    /**  @var int */
    public $expires = null;

    /**  @var string */
    public $memo = null;

    /**  @var string */
    public $payment_url = null;

    /**  @var string */
    public $merchant_data = null;


    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
        $descriptor = new Descriptor(__CLASS__, 'payments.PaymentDetails');

        // OPTIONAL STRING network = 1
        $f = new Field();
        $f->number = 1;
        $f->name = "network";
        $f->type = Protobuf::TYPE_STRING;
        $f->rule = Protobuf::RULE_OPTIONAL;
        $f->default = "main";
        $descriptor->addField($f);

        // REPEATED MESSAGE outputs = 2
        $f = new Field();
        $f->number = 2;
        $f->name = "outputs";
        $f->type = Protobuf::TYPE_MESSAGE;
        $f->rule = Protobuf::RULE_REPEATED;
        $f->reference = '\BitWasp\Bitcoin\Payments\Protobufs\Output';
        $descriptor->addField($f);

        // REQUIRED UINT64 time = 3
        $f = new Field();
        $f->number = 3;
        $f->name = "time";
        $f->type = Protobuf::TYPE_UINT64;
        $f->rule = Protobuf::RULE_REQUIRED;
        $descriptor->addField($f);

        // OPTIONAL UINT64 expires = 4
        $f = new Field();
        $f->number = 4;
        $f->name = "expires";
        $f->type = Protobuf::TYPE_UINT64;
        $f->rule = Protobuf::RULE_OPTIONAL;
        $descriptor->addField($f);

        // OPTIONAL STRING memo = 5
        $f = new Field();
        $f->number = 5;
        $f->name = "memo";
        $f->type = Protobuf::TYPE_STRING;
        $f->rule = Protobuf::RULE_OPTIONAL;
        $descriptor->addField($f);

        // OPTIONAL STRING payment_url = 6
        $f = new Field();
        $f->number = 6;
        $f->name = "payment_url";
        $f->type = Protobuf::TYPE_STRING;
        $f->rule = Protobuf::RULE_OPTIONAL;
        $descriptor->addField($f);

        // OPTIONAL BYTES merchant_data = 7
        $f = new Field();
        $f->number = 7;
        $f->name = "merchant_data";
        $f->type = Protobuf::TYPE_BYTES;
        $f->rule = Protobuf::RULE_OPTIONAL;
        $descriptor->addField($f);

        foreach (self::$__extensions as $cb) {
            $descriptor->addField($cb(), true);
        }

        return $descriptor;
    }

    /**
     * Check if <network> has a value
     *
     * @return boolean
     */
    public function hasNetwork()
    {
        return $this->_has(1);
    }

    /**
     * Clear <network> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function clearNetwork()
    {
        return $this->_clear(1);
    }

    /**
     * Get <network> value
     *
     * @return string
     */
    public function getNetwork()
    {
        return $this->_get(1);
    }

    /**
     * Set <network> value
     *
     * @param string $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function setNetwork($value)
    {
        return $this->_set(1, $value);
    }

    /**
     * Check if <outputs> has a value
     *
     * @return boolean
     */
    public function hasOutputs()
    {
        return $this->_has(2);
    }

    /**
     * Clear <outputs> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function clearOutputs()
    {
        return $this->_clear(2);
    }

    /**
     * Get <outputs> value
     *
     * @param int $idx
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\Output
     */
    public function getOutputs($idx = null)
    {
        return $this->_get(2, $idx);
    }

    /**
     * Set <outputs> value
     *
     * @param \BitWasp\Bitcoin\PaymentProtocol\Protobufs\Output $value
     * @param int $idx
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function setOutputs(\BitWasp\Bitcoin\PaymentProtocol\Protobufs\Output $value, $idx = null)
    {
        return $this->_set(2, $value, $idx);
    }

    /**
     * Get all elements of <outputs>
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\Output[]
     */
    public function getOutputsList()
    {
        return $this->_get(2);
    }

    /**
     * Add a new element to <outputs>
     *
     * @param \BitWasp\Bitcoin\PaymentProtocol\Protobufs\Output $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function addOutputs(\BitWasp\Bitcoin\PaymentProtocol\Protobufs\Output $value)
    {
        return $this->_add(2, $value);
    }

    /**
     * Check if <time> has a value
     *
     * @return boolean
     */
    public function hasTime()
    {
        return $this->_has(3);
    }

    /**
     * Clear <time> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function clearTime()
    {
        return $this->_clear(3);
    }

    /**
     * Get <time> value
     *
     * @return int
     */
    public function getTime()
    {
        return $this->_get(3);
    }

    /**
     * Set <time> value
     *
     * @param int $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function setTime($value)
    {
        return $this->_set(3, $value);
    }

    /**
     * Check if <expires> has a value
     *
     * @return boolean
     */
    public function hasExpires()
    {
        return $this->_has(4);
    }

    /**
     * Clear <expires> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function clearExpires()
    {
        return $this->_clear(4);
    }

    /**
     * Get <expires> value
     *
     * @return int
     */
    public function getExpires()
    {
        return $this->_get(4);
    }

    /**
     * Set <expires> value
     *
     * @param int $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function setExpires($value)
    {
        return $this->_set(4, $value);
    }

    /**
     * Check if <memo> has a value
     *
     * @return boolean
     */
    public function hasMemo()
    {
        return $this->_has(5);
    }

    /**
     * Clear <memo> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function clearMemo()
    {
        return $this->_clear(5);
    }

    /**
     * Get <memo> value
     *
     * @return string
     */
    public function getMemo()
    {
        return $this->_get(5);
    }

    /**
     * Set <memo> value
     *
     * @param string $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function setMemo($value)
    {
        return $this->_set(5, $value);
    }

    /**
     * Check if <payment_url> has a value
     *
     * @return boolean
     */
    public function hasPaymentUrl()
    {
        return $this->_has(6);
    }

    /**
     * Clear <payment_url> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function clearPaymentUrl()
    {
        return $this->_clear(6);
    }

    /**
     * Get <payment_url> value
     *
     * @return string
     */
    public function getPaymentUrl()
    {
        return $this->_get(6);
    }

    /**
     * Set <payment_url> value
     *
     * @param string $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function setPaymentUrl($value)
    {
        return $this->_set(6, $value);
    }

    /**
     * Check if <merchant_data> has a value
     *
     * @return boolean
     */
    public function hasMerchantData()
    {
        return $this->_has(7);
    }

    /**
     * Clear <merchant_data> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function clearMerchantData()
    {
        return $this->_clear(7);
    }

    /**
     * Get <merchant_data> value
     *
     * @return string
     */
    public function getMerchantData()
    {
        return $this->_get(7);
    }

    /**
     * Set <merchant_data> value
     *
     * @param string $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails
     */
    public function setMerchantData($value)
    {
        return $this->_set(7, $value);
    }
}
