<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Buffertools\BufferInterface;

class WitnessProgram
{
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
