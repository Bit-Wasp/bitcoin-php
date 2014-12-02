<?php

namespace Bitcoin;

use Bitcoin\Script;
use Bitcoin\Util\Buffer;
use Bitcoin\Util\Parser;

/**
 * Class TransactionOutput
 * @package Bitcoin
 */
class TransactionOutput implements TransactionOutputInterface
{

    /**
     * @var Buffer
     */
    protected $value;

    /**
     * @var Script
     */
    protected $script;

    /**
     * @var Buffer
     */
    protected $scriptBuf;

    /**
     * Initialize class
     */
    public function __construct()
    {
        return $this;
    }

    /**
     * Return the value of this output
     *
     * @return int|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of this output, in satoshis
     *
     * @param int|null $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Return an initialized script. Checks if already has a script
     * object. If not, returns script from scriptBuf (which can simply
     * be null).
     *
     * @return Script
     */
    public function getScript()
    {
        if ($this->script == null) {
            $this->script = new Script($this->getScriptBuf());
        }

        return $this->script;
    }

    /**
     * Return the current script buffer
     *
     * @return Buffer
     */
    public function getScriptBuf()
    {
        return $this->scriptBuf;
    }

    /**
     * Set Script Buffer
     *
     * @param Buffer $buffer
     * @return $this
     */
    public function setScriptBuf(Buffer $buffer)
    {
        $this->scriptBuf = $buffer;
        return $this;
    }

    /**
     * From a Parser instance, load what should be the script data.
     *
     * @param $parser
     * @return $this
     */
    public function fromParser(&$parser)
    {
        $this
            ->setValue(
                $parser
                    ->readBytes(8, true)
                    ->serialize('int')
            )
            ->setScriptBuf(
                $parser->getVarString()
            );
        return $this;
    }

    /**
     * Serialize the output into either hex ($type = hex),
     * or a byte string (default; $type = null)
     *
     * @param $type
     * @return string
     */
    public function serialize($type = null)
    {
        $parser = new Parser;
        $parser
            ->writeInt(8, $this->getValue(), true)
            ->writeWithLength(
                new Buffer($this->getScript()->serialize())
            );

        return $parser
            ->getBuffer()
            ->serialize($type);
    }

    /**
     * Return this object in the form of an array, compatible with bitcoind
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'value' => $this->getValue() / 1e8,
            'scriptPubKey' => $this->getScript()->toArray()
        );
    }
}
