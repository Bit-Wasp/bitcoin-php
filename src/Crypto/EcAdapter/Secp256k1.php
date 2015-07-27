<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Signature\CompactSignature;
use BitWasp\Bitcoin\Signature\Signature;
use BitWasp\Bitcoin\Signature\SignatureFactory;
use BitWasp\Bitcoin\Signature\SignatureInterface;
use Mdanter\Ecc\Primitives\GeneratorPoint;

class Secp256k1 extends BaseEcAdapter
{
    private $context;

    public function __construct(Math $math, GeneratorPoint $generator, $context)
    {
        if (get_resource_type($context) !== 'secp256k1_context_t') {
            throw new \InvalidArgumentException('Must pass initialized secp256k1 context');
        }

        $this->context = $context;
        parent::__construct($math, $generator);
    }

    /**
     * @return int
     */
    public function getAdapterName()
    {
        return self::SECP256K1;
    }

    /**
     * @param int|string $scalar
     * @return string
     */
    private function getBinaryScalar($scalar)
    {
        return str_pad(hex2bin($this->getMath()->decHex($scalar)), 32, chr(0), STR_PAD_LEFT);
    }

    /**
     * @param PrivateKeyInterface $oldPrivate
     * @param string $newBinary
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     */
    private function getRelatedPrivateKey(PrivateKeyInterface $oldPrivate, $newBinary)
    {
        return PrivateKeyFactory::fromHex(bin2hex($newBinary), $oldPrivate->isCompressed(), $this);
    }

    /**
     * @param PublicKeyInterface $oldPublic
     * @param string $newBinary
     * @return \BitWasp\Bitcoin\Key\PublicKey
     */
    private function getRelatedPublicKey(PublicKeyInterface $oldPublic, $newBinary)
    {
        return PublicKeyFactory::fromHex(bin2hex($newBinary), $this)->setCompressed($oldPublic->isCompressed());
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param RbgInterface $rbgInterface
     * @return Signature
     * @throws \Exception
     */
    public function sign(Buffer $messageHash, PrivateKeyInterface $privateKey, RbgInterface $rbgInterface = null)
    {
        $sigStr = '';
        $ret = \secp256k1_ecdsa_sign(
            $this->context,
            $messageHash->getBinary(),
            $privateKey->getBuffer()->getBinary(),
            $sigStr
        );

        if ($ret !== 1) {
            throw new \Exception('Secp256k1-php failed to sign data');
        }

        return SignatureFactory::fromHex(bin2hex($sigStr));
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param RbgInterface $rbg
     * @return CompactSignature
     * @throws \Exception
     */
    public function signCompact(Buffer $messageHash, PrivateKeyInterface $privateKey, RbgInterface $rbg = null)
    {
        $sigStr = '';
        $recid = 0;
        $ret = \secp256k1_ecdsa_sign_compact(
            $this->context,
            $messageHash->getBinary(),
            $privateKey->getBuffer()->getBinary(),
            $sigStr,
            $recid
        );

        if ($ret === 1) {
            $sigStr = new Buffer($sigStr);

            return new CompactSignature(
                $sigStr->slice(0, 32)->getInt(),
                $sigStr->slice(32, 32)->getInt(),
                $recid,
                $privateKey->isCompressed()
            );
        }

        throw new \Exception('Unable to create compact signature');
    }

    /**
     * @param CompactSignature $signature
     * @param Buffer $messageHash
     * @return \BitWasp\Bitcoin\Key\PublicKey
     * @throws \Exception
     */
    public function recoverCompact(Buffer $messageHash, CompactSignature $signature)
    {
        $pubkey = '';
        $ret = \secp256k1_ecdsa_recover_compact(
            $this->context,
            $messageHash->getBinary(),
            $signature->getBuffer()->slice(1)->getBinary(),
            (int)$signature->getRecoveryId(),
            (int)$signature->isCompressed(),
            $pubkey
        );

        if ($ret === 1) {
            $publicKey = PublicKeyFactory::fromHex(bin2hex($pubkey));
            return $publicKey->setCompressed($signature->isCompressed());
        }

        throw new \Exception('Unable to recover public key from compact signature');
    }

    /**
     * @param Buffer $messageHash
     * @param PublicKeyInterface $publicKey
     * @param SignatureInterface $signature
     * @return bool
     * @throws \Exception
     */
    public function verify(Buffer $messageHash, PublicKeyInterface $publicKey, SignatureInterface $signature)
    {
        $ret = \secp256k1_ecdsa_verify(
            $this->context,
            $messageHash->getBinary(),
            $signature->getBuffer()->getBinary(),
            $publicKey->getBuffer()->getBinary()
        );

        if ($ret === -1) {
            throw new \Exception('Secp256k1 verify: Invalid public key');
        } else if ($ret === -2) {
            throw new \Exception('Secp256k1 verify: Invalid signature');
        }

        return $ret === 1;
    }

    /**
     * @param Buffer $privateKey
     * @return bool
     */
    public function validatePrivateKey(Buffer $privateKey)
    {
        return (bool) \secp256k1_ec_seckey_verify($this->context, $privateKey->getBinary());
    }

    /**
     * @param Buffer $publicKey
     * @return bool
     */
    public function validatePublicKey(Buffer $publicKey)
    {
        return (bool) \secp256k1_ec_pubkey_verify($this->context, $publicKey->getBinary());
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @return \BitWasp\Bitcoin\Key\PublicKey
     * @throws \Exception
     */
    public function privateToPublic(PrivateKeyInterface $privateKey)
    {
        $publicKey = '';
        $ret = \secp256k1_ec_pubkey_create(
            $this->context,
            $privateKey->getBuffer()->getBinary(),
            (int)$privateKey->isCompressed(),
            $publicKey
        );

        if ($ret === 1) {
            $public = PublicKeyFactory::fromHex(bin2hex($publicKey), $this);
            return $public;
        }

        throw new \Exception('Unable to convert private to public key');
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param int|string $integer
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     * @throws \Exception
     */
    public function privateKeyMul(PrivateKeyInterface $privateKey, $integer)
    {
        $privKey = $privateKey->getBuffer()->getBinary(); // mod by reference
        $ret = (bool) \secp256k1_ec_privkey_tweak_mul(
            $this->context,
            $privKey,
            $this->getBinaryScalar($integer)
        );

        if ($ret === false) {
            throw new \Exception('Secp256k1 privkey tweak mul: failed');
        }

        return $this->getRelatedPrivateKey($privateKey, $privKey);
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param int|string $integer
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     * @throws \Exception
     */
    public function privateKeyAdd(PrivateKeyInterface $privateKey, $integer)
    {
        $privKey = $privateKey->getBuffer()->getBinary(); // mod by reference
        $ret = (bool) \secp256k1_ec_privkey_tweak_add(
            $this->context,
            $privKey,
            $this->getBinaryScalar($integer)
        );

        if ($ret === false) {
            throw new \Exception('Secp256k1 privkey tweak add: failed');
        }

        return $this->getRelatedPrivateKey($privateKey, $privKey);
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @param int|string $integer
     * @return \BitWasp\Bitcoin\Key\PublicKey
     * @throws \Exception
     */
    public function publicKeyAdd(PublicKeyInterface $publicKey, $integer)
    {
        $pubKey = $publicKey->getBuffer()->getBinary();
        $ret = (bool) \secp256k1_ec_pubkey_tweak_add(
            $this->context,
            $pubKey,
            $this->getBinaryScalar($integer)
        );

        if ($ret === false) {
            throw new \Exception('Secp256k1 pubkey tweak add: failed');
        }

        return $this->getRelatedPublicKey($publicKey, $pubKey);
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @param int|string $integer
     * @return \BitWasp\Bitcoin\Key\PublicKey
     * @throws \Exception
     */
    public function publicKeyMul(PublicKeyInterface $publicKey, $integer)
    {
        $pubKey = $publicKey->getBuffer()->getBinary();
        $ret = (bool) \secp256k1_ec_pubkey_tweak_mul(
            $this->context,
            $pubKey,
            $this->getBinaryScalar($integer)
        );

        if ($ret === false) {
            throw new \Exception('Secp256k1 pubkey tweak mul: failed');
        }

        return $this->getRelatedPublicKey($publicKey, $pubKey);
    }
}
