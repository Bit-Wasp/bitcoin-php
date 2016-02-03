<?php

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
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
     * @param Opcodes $opcodes
     */
    public function __construct(OutputScriptFactory $scriptPubKey, Opcodes $opcodes)
    {
        $this->scriptPubKey = $scriptPubKey;
        $this->opcodes = $opcodes;
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

    /**
     * Create a multisig witness program
     *
     * @param int $version
     * @param int $m
     * @param array $keys
     * @param bool|true $sort
     * @return WitnessProgram
     */
    public function multisig($version, $m, array $keys, $sort = true)
    {
        return new WitnessProgram($version, $this->scriptPubKey->multisig($m, $keys, $sort), $this->opcodes);
    }

    /**
     * Create a pay-to-pubkey witness program
     *
     * @param int $version
     * @param PublicKeyInterface  $publicKey
     * @return WitnessProgram
     */
    public function payToPubKey($version, PublicKeyInterface $publicKey)
    {
        return new WitnessProgram($version, $this->scriptPubKey->payToPubKey($publicKey), $this->opcodes);
    }

    /**
     * Create a pay-to-pubkey-hash witness program
     *
     * @param int $version
     * @param PublicKeyInterface  $publicKey
     * @return WitnessProgram
     */
    public function payToPubKeyHash($version, PublicKeyInterface $publicKey)
    {
        return new WitnessProgram($version, $this->scriptPubKey->payToPubKeyHash($publicKey), $this->opcodes);
    }
}
