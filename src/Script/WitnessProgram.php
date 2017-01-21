<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Buffertools\BufferInterface;

class WitnessProgram
{
    const V0 = 0;

    /**
     * @var int
     */
    private $version;

    /**
     * @var BufferInterface
     */
    private $program;

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * WitnessProgram constructor.
     * @param int $version
     * @param BufferInterface $program
     */
    public function __construct($version, BufferInterface $program)
    {
        if (self::V0 === $version) {
            $size = $program->getSize();
            if ($size === 20) {
                $this->script = ScriptFactory::scriptPubKey()->p2wkh($program);
            } else if ($size === 32) {
                $this->script = ScriptFactory::scriptPubKey()->p2wsh($program);
            } else {
                throw new \RuntimeException('Invalid size for V0 witness program - must be 20 or 32 bytes');
            }
        } else {
            throw new \InvalidArgumentException('Invalid witness version');
        }

        $this->version = $version;
        $this->program = $program;
    }

    /**
     * @param BufferInterface $program
     * @return WitnessProgram
     */
    public static function v0(BufferInterface $program)
    {
        return new self(self::V0, $program);
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return BufferInterface
     */
    public function getProgram()
    {
        return $this->program;
    }

    /**
     * @return ScriptInterface
     */
    public function getScript()
    {
        return $this->script;
    }
}
