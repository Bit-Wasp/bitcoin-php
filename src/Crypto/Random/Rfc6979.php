<?php

namespace BitWasp\Bitcoin\Crypto\Random;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Buffertools\Buffer;
use Mdanter\Ecc\Crypto\Key\PrivateKey as MdPrivateKey;
use Mdanter\Ecc\Primitives\GeneratorPoint;
use Mdanter\Ecc\Random\HmacRandomNumberGenerator;

class Rfc6979 extends HmacRandomNumberGenerator implements RbgInterface
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @param Math $math
     * @param GeneratorPoint $generator
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param string $algo
     */
    public function __construct(Math $math, GeneratorPoint $generator, PrivateKeyInterface $privateKey, Buffer $messageHash, $algo = 'sha256')
    {
        $this->math = $math;
        $mdPk = new MdPrivateKey($math, $generator, $privateKey->getSecretMultiplier());
        parent::__construct($math, $mdPk, $messageHash->getInt(), $algo);
    }

    /**
     * @param int $bytes
     * @return int|string
     */
    public function bytes($bytes)
    {
        $integer = $this->generate($this->generator->getOrder());
        return Buffer::hex($this->math->decHex($integer));
    }
}
