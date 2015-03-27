<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Signature\CompactSignature;
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
        return PublicKeyFactory::fromHex($hex, $this)->setCompressed($oldPublic->isCompressed());
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

        $ret = \secp256k1_ecdsa_sign($hashStr, $privateStr, $sigStr);
        if ($ret !== 1) {
            throw new \Exception('Secp256k1-php failed to sign data');
        }
        // Fix since secp256k1 doesn't know about hashtypes
        $sigStr .= SignatureHashInterface::SIGHASH_ALL;
        return SignatureFactory::fromHex(bin2hex($sigStr));
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param RbgInterface $rbg
     * @return CompactSignature
     * @throws \Exception
     */
    /*public function signCompact(PrivateKeyInterface $privateKey, Buffer $messageHash, RbgInterface $rbg = null)
    {
        $privateStr = $privateKey->getBuffer()->getBinary();
        $hashStr = $messageHash->getBinary();
        $sigStr = '';
        $sigLen = 0;
        $recid = 0;
        $ret = \secp256k1_ecdsa_sign_compact($hashStr, $privateStr, $sigStr, $recid);

        if ($ret === 1) {
            $math = $this->getMath();
            $r = $math->hexDec(bin2hex(substr($sigStr, 0, 32)));
            $s = $math->hexDec(bin2hex(substr($sigStr, 32, 32)));

            $sig = new CompactSignature($r, $s, $recid, $privateKey->isCompressed());
            return $sig;
        }

        throw new \Exception('Unable to create compact signature');
    }*/

    /**
     * @param CompactSignature $signature
     * @param Buffer $messageHash
     * @return \BitWasp\Bitcoin\Key\PublicKey
     * @throws \Exception
     */
    /*public function recoverCompact(CompactSignature $signature, Buffer $messageHash)
    {
        $pubkey = '';
        $recid = $signature->getFlags();
        $buf = $signature->getBuffer();
        $sig = $buf->getBinary();
        echo $buf->getHex()."\n";
        //echo "Try to validate: (".$signature->getBuffer()->getSize() . " " . bin2hex($sigStr) . "\n";
        $ret = \secp256k1_ecdsa_recover_compact($messageHash->getBinary(), $sig, $recid, $signature->isCompressed(), $pubkey);

        if ($ret === 1) {
            $publicKey = PublicKeyFactory::fromHex(bin2hex($pubkey));
            return $publicKey->setCompressed($signature->isCompressed());
        }

        throw new \Exception('Unable to recover public key from compact signature');
    }*/

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
        $ret = (bool) \secp256k1_ec_seckey_verify($privateKey->getBinary());
        return $ret;
    }

    /**
     * @param Buffer $publicKey
     * @return bool
     */
    public function validatePublicKey(Buffer $publicKey)
    {
        $ret = (bool) \secp256k1_ec_pubkey_verify($publicKey->getBinary());
        return $ret;
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @return \BitWasp\Bitcoin\Key\PublicKey
     * @throws \Exception
     */
    public function privateToPublic(PrivateKeyInterface $privateKey)
    {
        $publicKey = '';
        $privateStr = $privateKey->getBuffer()->getBinary();
        $ret = \secp256k1_ec_pubkey_create($privateStr, (int)$privateKey->isCompressed(), $publicKey);

        if ($ret === 1) {
            $public = PublicKeyFactory::fromHex(bin2hex($publicKey), $this);
            return $public;
        }

        throw new \Exception('Unable to convert private to public key');
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param $integer
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     * @throws \Exception
     */
    public function privateKeyMul(PrivateKeyInterface $privateKey, $integer)
    {
        $privKey = $privateKey->getBuffer()->getBinary(); // mod by reference
        $scalarStr = $this->getBinaryScalar($integer);

        $ret = (bool) \secp256k1_ec_privkey_tweak_mul($privKey, $scalarStr);
        if ($ret === false) {
            throw new \Exception('Secp256k1 privkey tweak mul: failed');
        }
        return $this->getRelatedPrivateKey($privateKey, $privKey);
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param $integer
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     * @throws \Exception
     */
    public function privateKeyAdd(PrivateKeyInterface $privateKey, $integer)
    {
        $privKey = $privateKey->getBuffer()->getBinary(); // mod by reference
        $scalarStr = $this->getBinaryScalar($integer);

        $ret = (bool) \secp256k1_ec_privkey_tweak_add($privKey, $scalarStr);
        if ($ret === false) {
            throw new \Exception('Secp256k1 privkey tweak add: failed');
        }
        return $this->getRelatedPrivateKey($privateKey, $privKey);
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @param $integer
     * @return \BitWasp\Bitcoin\Key\PublicKey
     * @throws \Exception
     */
    public function publicKeyAdd(PublicKeyInterface $publicKey, $integer)
    {
        $pubKey = $publicKey->getBuffer()->getBinary();
        $scalarStr = $this->getBinaryScalar($integer);

        $ret = (bool) \secp256k1_ec_pubkey_tweak_add($pubKey, $scalarStr);
        if ($ret === false) {
            throw new \Exception('Secp256k1 pubkey tweak add: failed');
        }
        return $this->getRelatedPublicKey($publicKey, $pubKey);
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @param $integer
     * @return \BitWasp\Bitcoin\Key\PublicKey
     * @throws \Exception
     */
    public function publicKeyMul(PublicKeyInterface $publicKey, $integer)
    {
        $pubKey = $publicKey->getBuffer()->getBinary();
        $pubkeyLen = strlen($pubKey);
        $scalarStr = $this->getBinaryScalar($integer);

        $ret = (bool) \secp256k1_ec_pubkey_tweak_mul($pubKey, $pubkeyLen, $scalarStr);
        if ($ret === false) {
            throw new \Exception('Secp256k1 pubkey tweak mul: failed');
        }
        return $this->getRelatedPublicKey($publicKey, $pubKey);
    }
}
