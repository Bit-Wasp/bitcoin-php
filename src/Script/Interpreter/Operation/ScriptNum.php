<?php


namespace BitWasp\Bitcoin\Script\Interpreter\Operation;

use BitWasp\Bitcoin\Exceptions\ScriptStackException;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Buffertools\Buffer;

class ScriptNum extends Buffer
{
    /**
     * @var Math
     */
    protected $math;

    /**
     * @var
     */
    protected $buffer;

    /**
     * @param Math $math
     * @param Flags $flags
     * @param Buffer $vch
     * @param int $size
     * @throws ScriptStackException
     * @throws \Exception
     */
    public function __construct(Math $math, Flags $flags, Buffer $vch, $size = 4)
    {
        if (!is_numeric($size)) {
            throw new \RuntimeException('ScriptNum size must be numeric');
        }

        $bufferSize = $vch->getSize();
        if ($bufferSize > $size) {
            throw new ScriptStackException(InterpreterInterface::VERIFY_MINIMALDATA, 'Script number overflow');
        }

        $str = $vch->getBinary();
        if ($flags->checkFlags(InterpreterInterface::VERIFY_MINIMALDATA) && $bufferSize > 0) {
            if ((ord($str[0]) & 0x7f) === 0) {
                if ($bufferSize <= 1 || (ord($str[1]) & 0x7f) === 0) {
                    throw new ScriptStackException(InterpreterInterface::VERIFY_MINIMALDATA, 'Non-minimally encoded integer');
                }
            }
        }

        $this->math = $math;
        parent::__construct($str, $size, $math);
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return new Buffer($this->buffer, null, $this->math);
    }
}
