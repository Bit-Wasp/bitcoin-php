<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\Random;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use Mdanter\Ecc\Crypto\Key\PrivateKey as MdPrivateKey;
use Mdanter\Ecc\Random\RandomGeneratorFactory;
use Mdanter\Ecc\Random\RandomNumberGeneratorInterface;

class Rfc6979 implements RbgInterface
{

    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var RandomNumberGeneratorInterface
     */
    private $hmac;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param PrivateKeyInterface $privateKey
     * @param BufferInterface $messageHash
     * @param string $algo
     */
    public function __construct(
        EcAdapterInterface $ecAdapter,
        PrivateKeyInterface $privateKey,
        BufferInterface $messageHash,
        string $algo = 'sha256'
    ) {
        $mdPk = new MdPrivateKey($ecAdapter->getMath(), $ecAdapter->getGenerator(), gmp_init($privateKey->getInt(), 10));
        $this->ecAdapter = $ecAdapter;
        $this->hmac = RandomGeneratorFactory::getHmacRandomGenerator($mdPk, gmp_init($messageHash->getInt(), 10), $algo);
    }

    /**
     * @param int $bytes
     * @return BufferInterface
     */
    public function bytes(int $bytes): BufferInterface
    {
        $integer = $this->hmac->generate($this->ecAdapter->getOrder());
        return Buffer::int(gmp_strval($integer, 10), $bytes);
    }
}
