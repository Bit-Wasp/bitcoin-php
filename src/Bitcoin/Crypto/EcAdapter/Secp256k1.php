<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Signature\Signature;
use BitWasp\Bitcoin\Signature\SignatureFactory;
use BitWasp\Bitcoin\Signature\SignatureHashInterface;
use BitWasp\Bitcoin\Signature\SignatureInterface;

class Secp256k1 extends BaseEcAdapter
{
    /**
     * @param $scalar
     * @return string
     */
    private function getBinaryScalar($scalar)
    {
        return str_pad(hex2bin($this->getMath()->decHex($scalar)), 32, chr(0), STR_PAD_LEFT);
    }

    /**
     * @param PrivateKeyInterface $oldPrivate
     * @param $newBinary
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     */
    private function getRelatedPrivateKey(PrivateKeyInterface $oldPrivate, $newBinary)
    {
        return PrivateKeyFactory::fromHex(bin2hex($newBinary), $oldPrivate->isCompressed(), $this);
    }

    /**
     * @param PublicKeyInterface $oldPublic
     * @param $newBinary
     * @return \BitWasp\Bitcoin\Key\PublicKey
     */
    private function getRelatedPublicKey(PublicKeyInterface $oldPublic, $newBinary)
    {
        $hex = bin2hex($newBinary);
        return PublicKeyFactory::fromHex($hex, $this);
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param RbgInterface $rbgInterface
     * @return Signature
     * @throws \Exception
     */
    public function sign(PrivateKeyInterface $privateKey, Buffer $messageHash, RbgInterface $rbgInterface = null)
    {
        $privateStr = $privateKey->getBuffer()->getBinary();
        $hashStr = $messageHash->getBinary();
        $sigStr = '';
        $signatureLength = 0;

        $ret = \secp256k1_ecdsa_sign($hashStr, $sigStr, $signatureLength, $privateStr);
        if ($ret !== 1) {
            throw new \Exception('Secp256k1-php failed to sign data');
        }
        // Fix since secp256k1 doesn't know about hashtypes
        $sigStr .= SignatureHashInterface::SIGHASH_ALL;
        return SignatureFactory::fromHex(bin2hex($sigStr));
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @param SignatureInterface $signature
     * @param Buffer $messageHash
     * @return bool
     * @throws \Exception
     */
    public function verify(PublicKeyInterface $publicKey, SignatureInterface $signature, Buffer $messageHash)
    {
        $publicStr = $publicKey->getBuffer()->getBinary();
        $sigStr = $signature->getBuffer()->getBinary();
        $hashStr = $messageHash->getBinary();
        $ret = \secp256k1_ecdsa_verify($hashStr, $sigStr, $publicStr);

        if ($ret === -1) {
            throw new \Exception('Secp256k1 verify: Invalid public key');
        } else if ($ret === -2) {
            throw new \Exception('Secp256k1 verify: Invalid signature');
        }

        return ($ret === 1)
            ? true
            : false;
    }

    /**
     * @param Buffer $privateKey
     * @return bool
     */
    public function validatePrivateKey(Buffer $privateKey)
    {
        $ret = \secp256k1_ec_seckey_verify($privateKey->getBinary());
        return ($ret === 1)
            ? true
            : false;
    }

    /**
     * @param Buffer $publicKey
     * @return bool
     */
    public function validatePublicKey(Buffer $publicKey)
    {
        $ret = \secp256k1_ec_pubkey_verify($publicKey->getBinary());
        return ($ret === 1)
            ? true
            : false;
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @return \BitWasp\Bitcoin\Key\PublicKey
     * @throws \Exception
     */
    public function privateToPublic(PrivateKeyInterface $privateKey)
    {
        // Make a fake public key from the generator, do scalar multiplication against privkey.
        $fakePubKey = PublicKeyFactory::fromPoint($this->getGenerator(), $privateKey->isCompressed(), $this);
        $privStr = $privateKey->getBuffer()->getInt();
        return $this->publicKeyMul($fakePubKey, $privStr);
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param $integer
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     */
    public function privateKeyMul(PrivateKeyInterface $privateKey, $integer)
    {
        $privKey = $privateKey->getBuffer()->getBinary(); // mod by reference
        $scalarStr = $this->getBinaryScalar($integer);

        $ret = \secp256k1_ec_privkey_tweak_mul($privKey, $scalarStr);
        return $this->getRelatedPrivateKey($privateKey, $privKey);
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param $integer
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     */
    public function privateKeyAdd(PrivateKeyInterface $privateKey, $integer)
    {
        $privKey = $privateKey->getBuffer()->getBinary(); // mod by reference
        $scalarStr = $this->getBinaryScalar($integer);

        $ret = \secp256k1_ec_privkey_tweak_add($privKey, $scalarStr);
        return $this->getRelatedPrivateKey($privateKey, $privKey);
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @param $integer
     * @return \BitWasp\Bitcoin\Key\PublicKey
     */
    public function publicKeyAdd(PublicKeyInterface $publicKey, $integer)
    {
        $pubKey = $publicKey->getBuffer()->getBinary();
        $scalarStr = $this->getBinaryScalar($integer);

        $ret = \secp256k1_ec_pubkey_tweak_add($pubKey, $scalarStr);
        return $this->getRelatedPublicKey($publicKey, $pubKey);
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @param $integer
     * @return \BitWasp\Bitcoin\Key\PublicKey
     */
    public function publicKeyMul(PublicKeyInterface $publicKey, $integer)
    {
        $pubKey = $publicKey->getBuffer()->getBinary();
        $pubkeyLen = strlen($pubKey);
        $scalarStr = $this->getBinaryScalar($integer);

        $ret = \secp256k1_ec_pubkey_tweak_mul($pubKey, $pubkeyLen, $scalarStr);

        return $this->getRelatedPublicKey($publicKey, $pubKey);
    }
}
