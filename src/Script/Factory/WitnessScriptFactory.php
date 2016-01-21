<?php

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\WitnessProgram;

class WitnessScriptFactory
{
    /**
     * @var OutputScriptFactory
     */
    private $scriptPubKey;

    /**
     * @var Opcodes
     */
    private $opcodes;

    /**
     * WitnessScriptFactory constructor.
     * @param OutputScriptFactory $scriptPubKey
     * @param P2shScriptFactory $redeemScript
     * @param Opcodes $opcodes
     */
    public function __construct(OutputScriptFactory $scriptPubKey, P2shScriptFactory $redeemScript, Opcodes $opcodes)
    {
        $this->scriptPubKey = $scriptPubKey;
        $this->redeemScript = $redeemScript;
        $this->opcodes = $opcodes;
    }

    /**
     * @param int $version
     * @param ScriptInterface $script
     * @return WitnessProgram
     */
    public function create($version, ScriptInterface $script)
    {
        return new WitnessProgram($version, $script);
    }

    /**
     * Parse a ScriptInterface into a WitnessProgram
     *
     * @param ScriptInterface $script
     * @return WitnessProgram
     */
    public function parse(ScriptInterface $script)
    {
        $parser = $script->getScriptParser();
        $decoded = $parser->decode();

        /**
         * @var Operation $versionPush
         * @var Operation $witnessPush
         */
        list ($versionPush, $witnessPush) = $decoded;
        $version = $versionPush->getData()->getInt();
        $program = new Script($witnessPush->getData());

        return new WitnessProgram($version, $program, $this->opcodes);
    }
}
