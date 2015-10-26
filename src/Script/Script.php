<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializable;

class Script extends Serializable implements ScriptInterface
{

    /**
     * @var Opcodes
     */
    private $opcodes;

    /**
     * @var null|string
     */
    protected $script;

    /**
     * @param Buffer|null $script
     * @param Opcodes|null $opcodes
     */
    public function __construct(Buffer $script = null, Opcodes $opcodes = null)
    {
        $this->script = $script instanceof Buffer ? $script->getBinary() : '';
        $this->opcodes = $opcodes ?: new Opcodes();
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return new Buffer($this->script);
    }

    /**
     * @return \BitWasp\Bitcoin\Address\ScriptHashAddress
     */
    public function getAddress()
    {
        return AddressFactory::fromScript($this);
    }

    /**
     * @return ScriptParser
     */
    public function getScriptParser()
    {
        return new ScriptParser(Bitcoin::getMath(), $this);
    }

    /**
     * Get all opcodes (OP_X => opcode)
     *
     * @return Opcodes
     */
    public function getOpCodes()
    {
        return $this->opcodes;
    }

    /**
     * Return a buffer containing the hash of this script.
     *
     * @return \BitWasp\Buffertools\Buffer
     */
    public function getScriptHash()
    {
        return Hash::sha256ripe160($this->getBuffer());
    }

    /**
     * @param bool|true $accurate
     * @return int
     */
    public function countSigOps($accurate = true)
    {
        $count = 0;
        $ops = $this->opcodes;
        $parser = $this->getScriptParser();
        $op = 0xff;
        $pushData = new Buffer();
        $lastOp = 0xff;
        while ($parser->next($op, $pushData)) {
            if ($op > 78) {
                // None of these are pushdatas, so just an opcode
                if ($ops->isOp($op, 'OP_CHECKSIG') || $ops->isOp($op, 'OP_CHECKSIGVERIFY')) {
                    $count++;
                } elseif ($ops->isOp($op, 'OP_CHECKMULTISIG') || $ops->isOp($op, 'OP_CHECKMULTISIGVERIFY')) {
                    if ($accurate && ($ops->cmp($lastOp, 'OP_1') >= 0 && $ops->cmp($lastOp, 'OP_16') <= 0)) {
                        $c = ($lastOp - ($ops->getOpByName('OP_1') - 1));
                        $count += $c;
                    } else {
                        $count += 20;
                    }
                }

                $lastOp = $op;
            }
        }

        return $count;
    }

    /**
     * @param ScriptInterface $scriptSig
     * @return int
     */
    public function countP2shSigOps(ScriptInterface $scriptSig)
    {
        if (ScriptFactory::scriptPubKey()
            ->classify($this)
            ->isPayToScriptHash() === false
        ) {
            return $this->countSigOps(true);
        }

        $parsed = $scriptSig->getScriptParser();
        $op = 0xff;
        $push = new Buffer();
        $data = null;
        while ($parsed->next($op, $push)) {
            if ($this->opcodes->cmp($op, 'OP_16') > 0) {
                return 0;
            }

            if ($push instanceof Buffer) {
                $data = $push;
            }
        }

        if (!$data instanceof Buffer) {
            return 0;
        }

        return (new Script($push))->countSigOps(true);
    }

    /**
     * @return bool
     */
    public function isPushOnly()
    {
        $pushOnly = true;
        foreach ($this->getScriptParser()->parse() as $entity) {
            $pushOnly &= $entity instanceof Buffer;
        }
        return $pushOnly;
    }
}
