<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Crypto\Hash;

class WitnessProgram extends Script
{
    /**
     * @var int
     */
    private $version;

    /**
     * @var ScriptInterface
     */
    private $program;

    /**
     * WitnessProgram constructor.
     * @param int $version
     * @param ScriptInterface $program
     * @param Opcodes $opcodes
     */
    public function __construct($version, ScriptInterface $program, Opcodes $opcodes = null)
    {
        $internal = ScriptFactory::create()->int($version);
        switch ($version) {
            case 0:
                $internal->push($program->getBuffer());
                break;
            case 1:
                $internal->push(Hash::sha256($program->getBuffer()));
                break;
            default:
                throw new \RuntimeException('Only version 0 and version 1 scripts are supported');
        }

        $this->version = $version;
        $this->program = $program;

        parent::__construct($internal->getScript()->getBuffer(), $opcodes);
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return ScriptInterface
     */
    public function getProgram()
    {
        return $this->program;
    }
}
