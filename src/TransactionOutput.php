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
     *
     */
    public function __construct()
    {
        return $this;
    }

    public function fromParser(&$parser)
    {
        $this
            ->setValue(
                $parser->readBytes(8, true)
            )
            ->setScriptBuf(
                $parser->readBytes(
                    $parser->getVarInt()->serialize('int')
                )
            );
        return $this;
    }

    /**
     * Serialize a
     * @param $type
     * @return string
     */
    public function serialize($type = null)
    {
        return (new Parser)
            ->writeInt(8, $this->getValue()->serialize('int'), true)
            ->writeWithLength(
                new Buffer($this->getScript())
            )
            ->getBuffer()
            ->serialize($type);
    }


    /**
     * @return Buffer|mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param Buffer $value
     * @return $this
     */
    public function setValue(Buffer $value)
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
     * @return Buffer
     */
    public function getScriptBuf()
    {
        return $this->scriptBuf;
    }

    /**
     * @param Buffer $buffer
     * @return $this
     */
    public function setScriptBuf(Buffer $buffer)
    {
        $this->scriptBuf = $buffer;
        return $this;
    }

}
