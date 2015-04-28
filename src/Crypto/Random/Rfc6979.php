<?php

namespace BitWasp\Bitcoin\Crypto\Random;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Buffertools\Buffer;
use Mdanter\Ecc\Crypto\Key\PrivateKey as MdPrivateKey;
use Mdanter\Ecc\Random\HmacRandomNumberGenerator;

class Rfc6979 implements RbgInterface
{

    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var HmacRandomNumberGenerator
     */
    private $hmac;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param string $algo
     */
    public function __construct(
        EcAdapterInterface $ecAdapter,
        PrivateKeyInterface $privateKey,
        Buffer $messageHash,
        $algo = 'sha256'
    ) {
        $mdPk = new MdPrivateKey($ecAdapter->getMath(), $ecAdapter->getGenerator(), $privateKey->getSecretMultiplier());
        $this->ecAdapter = $ecAdapter;
        $this->hmac = new HmacRandomNumberGenerator($ecAdapter->getMath(), $mdPk, $messageHash->getInt(), $algo);
    }

    /**
     * @param int $bytes
     * @return Buffer
     */
    public function bytes($bytes)
    {
        $integer = $this->hmac->generate($this->ecAdapter->getGenerator()->getOrder());
        return Buffer::hex($this->ecAdapter->getMath()->decHex($integer));
    }
}
