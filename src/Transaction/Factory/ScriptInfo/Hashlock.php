<?php

namespace BitWasp\Bitcoin\Transaction\Factory\ScriptInfo;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Buffertools\BufferInterface;

/**
 * Generic classifier of hashlocks, to be used by
 * the InputSigner. This is strictly internal to this
 * project.
 *
 * At the moment, tolerates:
 * []
 * @package BitWasp\Bitcoin\Transaction\Factory\ScriptInfo
 * @internal
 */
class Hashlock
{
    /**
     * @var array
     */
    private static $sizeMap = [
        Opcodes::OP_RIPEMD160 => 20,
        Opcodes::OP_SHA1 => 20,
        Opcodes::OP_SHA256 => 32,
        Opcodes::OP_HASH256 => 32,
        Opcodes::OP_HASH160 => 20,
    ];

    /**
     * @var BufferInterface
     */
    private $hash;

    /**
     * @var int
     */
    private $opcode;

    /**
     * Hashlock constructor.
     * @param BufferInterface $hash
     * @param $opcode
     */
    public function __construct(BufferInterface $hash, $opcode)
    {
        if (!array_key_exists($opcode, self::$sizeMap)) {
            throw new \RuntimeException("Unknown opcode");
        }
        $size = self::$sizeMap[$opcode];
        if ($hash->getSize() !== $size) {
            throw new \RuntimeException("Unexpected size for hash");
        }

        $this->hash = $opcode;
        $this->opcode = $opcode;
    }

    /**
     * @return BufferInterface
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @return int
     */
    public function getOpcode()
    {
        return $this->opcode;
    }


    /**
     * @param BufferInterface $preimage
     * @return bool
     */
    public function checkPreimage(BufferInterface $preimage)
    {
        switch($this->opcode) {
            case Opcodes::OP_SHA1:
                $hash = Hash::sha1($preimage);
                break;
            case Opcodes::OP_SHA256:
                $hash = Hash::sha256($preimage);
                break;
            case Opcodes::OP_RIPEMD160:
                $hash = Hash::ripemd160($preimage);
                break;
            case Opcodes::OP_HASH160:
                $hash = Hash::sha256ripe160($preimage);
                break;
            case Opcodes::OP_HASH256:
                $hash = Hash::sha256d($preimage);
                break;
            default:
                throw new \RuntimeException("Missing hash function in Hashlock, but opcode was allowed by constructor..");
        }

        return $hash->equals($preimage);
    }

}
