<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Math\Math;
use Mdanter\Ecc\GeneratorPoint;
use BitWasp\Bitcoin\Signature\SignatureCollection;
use BitWasp\Bitcoin\Signature\SignatureInterface;
use BitWasp\Bitcoin\Buffer;

abstract class BaseEcAdapter implements EcAdapterInterface
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var GeneratorPoint
     */
    private $generator;

    /**
     * @param Math $math
     * @param GeneratorPoint $G
     */
    public function __construct(Math $math, GeneratorPoint $G)
    {
        $this->math = $math;
        $this->generator = $G;
    }

    /**
     * @return Math
     */
    public function getMath()
    {
        return $this->math;
    }

    /**
     * @return GeneratorPoint
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * @param SignatureCollection $signatures
     * @param Buffer $messageHash
     * @param \BitWasp\Bitcoin\Key\PublicKeyInterface[] $publicKeys
     * @return SignatureInterface[]
     */
    public function associateSigs(SignatureCollection $signatures, Buffer $messageHash, array $publicKeys)
    {
        $sigCount = count($signatures);
        $linked = [];

        foreach ($signatures->getSignatures() as $c => $signature) {
            foreach ($publicKeys as $key) {
                $verify = $this->verify($key, $signature, $messageHash);
                if ($verify) {
                    $linked[$key->getPubKeyHash()] = $signature;
                    if (count($linked) == $sigCount) {
                        break 2;
                    } else {
                        break;
                    }
                }
            }
        }

        return $linked;
    }
}
