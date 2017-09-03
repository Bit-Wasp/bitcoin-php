<?php

namespace BitWasp\Bitcoin\Script\ScriptInfo;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Buffertools\BufferInterface;

class PayToPubkey
{
    /**
     * @var BufferInterface
     */
    private $publicKey;

    /**
     * @var bool
     */
    private $verify;

    /**
     * @var int
     */
    private $opcode;

    /**
     * PayToPubkey constructor.
     * @param int $opcode
     * @param BufferInterface $publicKey
     * @param bool $allowVerify
     */
    public function __construct($opcode, BufferInterface $publicKey, $allowVerify = false)
    {
        if ($opcode === Opcodes::OP_CHECKSIG) {
            $verify = false;
        } else if ($allowVerify && $opcode === Opcodes::OP_CHECKSIGVERIFY) {
            $verify = true;
        } else {
            throw new \InvalidArgumentException('Malformed pay-to-pubkey script - invalid opcode');
        }

        $this->verify = $verify;
        $this->opcode = $opcode;
        $this->publicKey = $publicKey;
    }

    /**
     * @param Operation[] $chunks
     * @param bool $allowVerify
     * @return static
     */
    public static function fromDecodedScript(array $chunks, $allowVerify = false)
    {
        if (count($chunks) !== 2 || !$chunks[0]->isPush() || $chunks[1]->isPush()) {
            throw new \InvalidArgumentException('Malformed pay-to-pubkey script');
        }

        return new static($chunks[1]->getOp(), $chunks[0]->getData(), $allowVerify);
    }

    /**
     * @param ScriptInterface $script
     * @param bool $allowVerify
     * @return PayToPubkey
     */
    public static function fromScript(ScriptInterface $script, $allowVerify = false)
    {
        return static::fromDecodedScript($script->getScriptParser()->decode(), $allowVerify);
    }

    public function getType()
    {
        return ScriptType::P2PK;
    }

    /**
     * @return int
     */
    public function getRequiredSigCount()
    {
        return 1;
    }

    /**
     * @return int
     */
    public function getKeyCount()
    {
        return 1;
    }

    public function isChecksigVerify()
    {
        return $this->verify;
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return bool
     */
    public function checkInvolvesKey(PublicKeyInterface $publicKey)
    {
        return $publicKey->getBuffer()->equals($this->publicKey);
    }

    /**
     * @return BufferInterface
     */
    public function getKeyBuffer()
    {
        return $this->publicKey;
    }
}
