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
     * WitnessProgram constructor.
     * @param int $version
     * @param BufferInterface $program
     */
    public function __construct($version, BufferInterface $program)
    {
        $this->version = $version;
        $this->program = $program;
    }

    /**
     * @param BufferInterface $program
     * @return WitnessProgram
     */
    public static function v0(BufferInterface $program)
    {
        if ($program->getSize() === 20) {
            return new self(self::V0, $program);
        } else if ($program->getSize() === 20) {
            return new self(self::V0, $program);
        } else {
            throw new \RuntimeException('Invalid size for V0 witness program - must be 20 or 32 bytes');
        }
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
        return ScriptFactory::create()
            ->int($this->version)
            ->push($this->program)
            ->getScript();
    }
}
