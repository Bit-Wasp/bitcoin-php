<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Buffertools\Buffer;

class RedeemScript extends Script
{
    /**
     * @var int|string
     */
    private $m;

    /**
     * @var array
     */
    private $keys = [];

    /**
     * @param int|string $m
     * @param \BitWasp\Bitcoin\Key\PublicKeyInterface[] $keys
     */
    public function __construct($m, array $keys)
    {
        parent::__construct();

        $n = count($keys);
        if ($m > $n) {
            throw new \LogicException('Required number of sigs exceeds number of public keys');
        }
        if ($n > 16) {
            throw new \LogicException('Number of public keys is greater than 16');
        }

        $ops = $this->getOpCodes();
        $opM = $ops->getOp($ops->getOpByName('OP_1') - 1 + $m);
        $opN = $ops->getOp($ops->getOpByName('OP_1') - 1 + $n);

        $this->op($opM);
        foreach ($keys as $key) {
            if (!$key instanceof PublicKeyInterface) {
                throw new \LogicException('Values in $keys[] must be a PublicKey');
            }

            $this->keys[] = $key;
            $this->push($key->getBuffer());
        }
        $this
            ->op($opN)
            ->op('OP_CHECKMULTISIG');

        $this->m = $m;
    }

    /**
     * @param ScriptInterface $script
     * @return RedeemScript
     */
    public static function fromScript(ScriptInterface $script)
    {
        $publicKeys = [];
        $parse = $script->getScriptParser()->parse();
        $opCodes = $script->getOpcodes();
        $m = $opCodes->getOpByName($parse[0]) - $opCodes->getOpByName('OP_1') + 1 ;
        foreach (array_slice($parse, 1, -2) as $item) {
            if (!$item instanceof Buffer) {
                throw new \RuntimeException('Unable to load public key');
            }
            $publicKeys[] = PublicKeyFactory::fromHex($item->getHex());
        }

        if (count($publicKeys) == 0) {
            throw new \LogicException('No public keys found in script');
        }

        return new self($m, $publicKeys);
    }

    /**
     * @return Script
     */
    public function getOutputScript()
    {
        return ScriptFactory::scriptPubKey()->payToScriptHash($this);
    }

    /**
     * @return int
     */
    public function getRequiredSigCount()
    {
        return $this->m;
    }

    /**
     * @return int
     */
    public function getKeyCount()
    {
        return count($this->keys);
    }
    /**
     * @return PublicKeyInterface[]
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * @param $index
     * @return mixed
     */
    public function getKey($index)
    {
        if (!isset($this->keys[$index])) {
            throw new \LogicException('No key at that index');
        }

        return $this->keys[$index];
    }
}
