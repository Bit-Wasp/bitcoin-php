<?php

namespace BitWasp\Bitcoin\Payments\Protobufs;

class Output extends \DrSlump\Protobuf\Message
{

    /**  @var int */
    public $amount = 0;

    /**  @var string */
    public $script = null;


    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
        $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, 'payments.Output');

        // OPTIONAL UINT64 amount = 1
        $f = new \DrSlump\Protobuf\Field();
        $f->number = 1;
        $f->name = "amount";
        $f->type = \DrSlump\Protobuf::TYPE_UINT64;
        $f->rule = \DrSlump\Protobuf::RULE_OPTIONAL;
        $f->default = 0;
        $descriptor->addField($f);

        // REQUIRED BYTES script = 2
        $f = new \DrSlump\Protobuf\Field();
        $f->number = 2;
        $f->name = "script";
        $f->type = \DrSlump\Protobuf::TYPE_BYTES;
        $f->rule = \DrSlump\Protobuf::RULE_REQUIRED;
        $descriptor->addField($f);

        foreach (self::$__extensions as $cb) {
            $descriptor->addField($cb(), true);
        }

        return $descriptor;
    }

    /**
     * Check if <amount> has a value
     *
     * @return boolean
     */
    public function hasAmount()
    {
        return $this->_has(1);
    }

    /**
     * Clear <amount> value
     *
     * @return \BitWasp\Bitcoin\Payments\Protobufs\Output
     */
    public function clearAmount()
    {
        return $this->_clear(1);
    }

    /**
     * Get <amount> value
     *
     * @return int
     */
    public function getAmount()
    {
        return $this->_get(1);
    }

    /**
     * Set <amount> value
     *
     * @param int $value
     * @return \BitWasp\Bitcoin\Payments\Protobufs\Output
     */
    public function setAmount($value)
    {
        return $this->_set(1, $value);
    }

    /**
     * Check if <script> has a value
     *
     * @return boolean
     */
    public function hasScript()
    {
        return $this->_has(2);
    }

    /**
     * Clear <script> value
     *
     * @return \BitWasp\Bitcoin\Payments\Protobufs\Output
     */
    public function clearScript()
    {
        return $this->_clear(2);
    }

    /**
     * Get <script> value
     *
     * @return string
     */
    public function getScript()
    {
        return $this->_get(2);
    }

    /**
     * Set <script> value
     *
     * @param string $value
     * @return \BitWasp\Bitcoin\Payments\Protobufs\Output
     */
    public function setScript($value)
    {
        return $this->_set(2, $value);
    }
}