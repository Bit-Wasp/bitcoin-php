<?php

namespace BitWasp\Bitcoin\PaymentProtocol\Protobufs;

use \DrSlump\Protobuf\Message;

class X509Certificates extends Message
{

    /**  @var string[] */
    public $certificate = array();


    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
        $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, 'payments.X509Certificates');

        // REPEATED BYTES certificate = 1
        $f = new \DrSlump\Protobuf\Field();
        $f->number = 1;
        $f->name = 'certificate';
        $f->type = \DrSlump\Protobuf::TYPE_BYTES;
        $f->rule = \DrSlump\Protobuf::RULE_REPEATED;
        $descriptor->addField($f);

        foreach (self::$__extensions as $cb) {
            $descriptor->addField($cb(), true);
        }

        return $descriptor;
    }

    /**
     * Check if <certificate> has a value
     *
     * @return boolean
     */
    public function hasCertificate()
    {
        return $this->_has(1);
    }

    /**
     * Clear <certificate> value
     *
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\X509Certificates
     */
    public function clearCertificate()
    {
        return $this->_clear(1);
    }

    /**
     * Get <certificate> value
     *
     * @param int $idx
     * @return string
     */
    public function getCertificate($idx = null)
    {
        return $this->_get(1, $idx);
    }

    /**
     * Set <certificate> value
     *
     * @param string $value
     * @param int $idx
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\X509Certificates
     */
    public function setCertificate($value, $idx = null)
    {
        return $this->_set(1, $value, $idx);
    }

    /**
     * Get all elements of <certificate>
     *
     * @return string[]
     */
    public function getCertificateList()
    {
        return $this->_get(1);
    }

    /**
     * Add a new element to <certificate>
     *
     * @param string $value
     * @return \BitWasp\Bitcoin\PaymentProtocol\Protobufs\X509Certificates
     */
    public function addCertificate($value)
    {
        return $this->_add(1, $value);
    }
}
