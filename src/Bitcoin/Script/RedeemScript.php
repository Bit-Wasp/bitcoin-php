<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 14/03/15
 * Time: 20:49
 */

namespace Afk11\Bitcoin\Script;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Key\PublicKey;
use Afk11\Bitcoin\Key\PublicKeyFactory;
use Afk11\Bitcoin\Key\PublicKeyInterface;
use Afk11\Bitcoin\Signature\Signer;

class RedeemScript extends Script
{
    /**
     * @var int
     */
    private $m;

    /**
     * @var array
     */
    private $keys = [];

    /**
     * @param integer $m
     * @param \Afk11\Bitcoin\Key\PublicKeyInterface[] $keys
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
            if (!$key instanceof PublicKey) {
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
     * @return \Afk11\Bitcoin\Buffer|int
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
            throw new \LogicException('No key at index ' . $index);
        }

        return $this->keys[$index];
    }
}
