<?php

namespace BitWasp\Bitcoin\PaymentProtocol\Protobufs;

use \DrSlump\Protobuf;
use \DrSlump\Protobuf\Descriptor;
use \DrSlump\Protobuf\Field;
use \DrSlump\Protobuf\Message;

class PaymentRequest extends Message
{

    /**  @var int */
    public $payment_details_version = 1;

    /**  @var string */
    public $pki_type = 'none';

    /**  @var string */
    public $pki_data ;

    /**  @var string */
    public $serialized_payment_details ;

    /**  @var string */
    public $signature ;

    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
        $descriptor = new Descriptor(__CLASS__, 'payments.PaymentRequest');

        // OPTIONAL UINT32 payment_details_version = 1
        $f = new Field();
        $f->number = 1;
        $f->name = 'payment_details_version';
        $f->type = Protobuf::TYPE_UINT32;
        $f->rule = Protobuf::RULE_OPTIONAL;
        $f->default = 1;
        $descriptor->addField($f);

        // OPTIONAL STRING pki_type = 2
        $f = new Field();
        $f->number = 2;
        $f->name = 'pki_type';
        $f->type = Protobuf::TYPE_STRING;
        $f->rule = Protobuf::RULE_OPTIONAL;
        $f->default = 'none';
        $descriptor->addField($f);

        // OPTIONAL BYTES pki_data = 3
        $f = new Field();
        $f->number = 3;
        $f->name = 'pki_data';
        $f->type = Protobuf::TYPE_BYTES;
        $f->rule = Protobuf::RULE_OPTIONAL;
        $descriptor->addField($f);

        // REQUIRED BYTES serialized_payment_details = 4
        $f = new Field();
        $f->number = 4;
        $f->name = 'serialized_payment_details';
        $f->type = Protobuf::TYPE_BYTES;
        $f->rule = Protobuf::RULE_REQUIRED;
        $descriptor->addField($f);

        // OPTIONAL BYTES signature = 5
        $f = new Field();
        $f->number = 5;
        $f->name = 'signature';
        $f->type = Protobuf::TYPE_BYTES;
        $f->rule = Protobuf::RULE_OPTIONAL;
        $descriptor->addField($f);

        foreach (self::$__extensions as $cb) {
            $descriptor->addField($cb(), true);
        }

        return $descriptor;
    }

    /**
     * Check if <payment_details_version> has a value
     *
     * @return boolean
     */
    public function hasPaymentDetailsVersion()
    {
        return $this->_has(1);
    }

    /**
     * Clear <payment_details_version> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest
     */
    public function clearPaymentDetailsVersion()
    {
        return $this->_clear(1);
    }

    /**
     * Get <payment_details_version> value
     *
     * @return int
     */
    public function getPaymentDetailsVersion()
    {
        return $this->_get(1);
    }

    /**
     * Set <payment_details_version> value
     *
     * @param int $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest
     */
    public function setPaymentDetailsVersion($value)
    {
        return $this->_set(1, $value);
    }

    /**
     * Check if <pki_type> has a value
     *
     * @return boolean
     */
    public function hasPkiType()
    {
        return $this->_has(2);
    }

    /**
     * Clear <pki_type> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest
     */
    public function clearPkiType()
    {
        return $this->_clear(2);
    }

    /**
     * Get <pki_type> value
     *
     * @return string
     */
    public function getPkiType()
    {
        return $this->_get(2);
    }

    /**
     * Set <pki_type> value
     *
     * @param string $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest
     */
    public function setPkiType($value)
    {
        return $this->_set(2, $value);
    }

    /**
     * Check if <pki_data> has a value
     *
     * @return boolean
     */
    public function hasPkiData()
    {
        return $this->_has(3);
    }

    /**
     * Clear <pki_data> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest
     */
    public function clearPkiData()
    {
        return $this->_clear(3);
    }

    /**
     * Get <pki_data> value
     *
     * @return string
     */
    public function getPkiData()
    {
        return $this->_get(3);
    }

    /**
     * Set <pki_data> value
     *
     * @param string $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest
     */
    public function setPkiData($value)
    {
        return $this->_set(3, $value);
    }

    /**
     * Check if <serialized_payment_details> has a value
     *
     * @return boolean
     */
    public function hasSerializedPaymentDetails()
    {
        return $this->_has(4);
    }

    /**
     * Clear <serialized_payment_details> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest
     */
    public function clearSerializedPaymentDetails()
    {
        return $this->_clear(4);
    }

    /**
     * Get <serialized_payment_details> value
     *
     * @return string
     */
    public function getSerializedPaymentDetails()
    {
        return $this->_get(4);
    }

    /**
     * Set <serialized_payment_details> value
     *
     * @param string $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest
     */
    public function setSerializedPaymentDetails($value)
    {
        return $this->_set(4, $value);
    }

    /**
     * Check if <signature> has a value
     *
     * @return boolean
     */
    public function hasSignature()
    {
        return $this->_has(5);
    }

    /**
     * Clear <signature> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest
     */
    public function clearSignature()
    {
        return $this->_clear(5);
    }

    /**
     * Get <signature> value
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->_get(5);
    }

    /**
     * Set <signature> value
     *
     * @param string $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest
     */
    public function setSignature($value)
    {
        return $this->_set(5, $value);
    }
}
