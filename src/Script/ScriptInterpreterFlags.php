<?php

namespace BitWasp\Bitcoin\Script;


class ScriptInterpreterFlags
{
    const VERIFY_NONE = 0;
    const VERIFY_P2SH = 1;
    const VERIFY_STRICTENC = 2;
    const VERIFY_DERSIG = 4;
    const VERIFY_LOW_S = 8;
    const VERIFY_NULL_DUMMY = 16;
    const VERIFY_SIGPUSHONLY = 32;
    const VERIFY_MINIMALDATA = 64;
    const VERIFY_DISCOURAGE_UPGRADABLE_NOPS = 128;
    const VERIFY_CLEAN_STACK = 256;

    /**
     * @var int
     */
    private $flags;

    /**
     * @var int
     */
    private $maxBytes = 10000;

    /**
     * @var int
     */
    private $maxElementSize = 520;

    /**
     * @var bool
     */
    private $checkDisabledOpcodes = false;

    /**
     * @param $flags
     * @param bool $checkDisabledOpcodes
     */
    public function __construct($flags, $checkDisabledOpcodes = false)
    {
        if (!is_bool($checkDisabledOpcodes)) {
            throw new \InvalidArgumentException('CheckDisabledOpcodes must be a boolean');
        }

        $this->flags = $flags;
        $this->checkDisabledOpcodes = $checkDisabledOpcodes;
    }

    /**
     * @return int
     */
    public function getMaxBytes()
    {
        return $this->maxBytes;
    }

    /**
     * @return int
     */
    public function getMaxElementSize()
    {
        return $this->maxElementSize;
    }

    /**
     * @return bool
     */
    public function checkDisabledOpcodes()
    {
        return $this->checkDisabledOpcodes;
    }

    /**
     * @param $flags
     * @return int
     */
    public function checkFlags($flags)
    {
        return $this->flags & $flags;
    }

    /**
     * @return ScriptInterpreterFlags
     */
    public static function defaults()
    {
        return new self(
            self::VERIFY_P2SH | self::VERIFY_STRICTENC | self::VERIFY_DERSIG |
            self::VERIFY_LOW_S | self::VERIFY_NULL_DUMMY | self:: VERIFY_SIGPUSHONLY |
            self::VERIFY_DISCOURAGE_UPGRADABLE_NOPS | self::VERIFY_CLEAN_STACK,
            true
        );
    }
}
