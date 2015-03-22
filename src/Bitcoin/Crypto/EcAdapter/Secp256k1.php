<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter {
    use BitWasp\Bitcoin\Buffer;
    use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
    use BitWasp\Bitcoin\Key\PrivateKeyFactory;
    use BitWasp\Bitcoin\Key\PrivateKeyInterface;
    use BitWasp\Bitcoin\Key\PublicKeyFactory;
    use BitWasp\Bitcoin\Key\PublicKeyInterface;
    use BitWasp\Bitcoin\Math\Math;
    use BitWasp\Bitcoin\Signature\Signature;
    use BitWasp\Bitcoin\Signature\SignatureFactory;
    use BitWasp\Bitcoin\Signature\SignatureHashInterface;
    use BitWasp\Bitcoin\Signature\SignatureInterface;
    use Mdanter\Ecc\GeneratorPoint;


    class Secp256k1 implements EcAdapterInterface
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
         * @param $scalar
         * @return string
         */
        private function getBinaryScalar($scalar)
        {
            return str_pad(hex2bin($this->math->decHex($scalar)), 32, chr(0), STR_PAD_LEFT);
        }

        /**
         * @param PrivateKeyInterface $oldPrivate
         * @param $newBinary
         * @return \BitWasp\Bitcoin\Key\PrivateKey
         */
        private function getRelatedPrivateKey(PrivateKeyInterface $oldPrivate, $newBinary)
        {
            return PrivateKeyFactory::fromHex(bin2hex($newBinary), $oldPrivate->isCompressed(), $this->math, $this->generator);
        }

        /**
         * @param PublicKeyInterface $oldPublic
         * @param $newBinary
         * @return \BitWasp\Bitcoin\Key\PublicKey
         */
        private function getRelatedPublicKey(PublicKeyInterface $oldPublic, $newBinary)
        {
            return PublicKeyFactory::fromHex(bin2hex($newBinary), $oldPublic->isCompressed(), $this->math, $this->generator);
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
            if ($ret !== 0) {
                throw new \Exception('Secp256k1-php failed to sign data');
            }
            // Fix since secp256k1 doesn't know about hashtypes
            $sigStr .= SignatureHashInterface::SIGHASH_ALL;

            $signature = SignatureFactory::fromHex($sigStr);
            return $signature;
        }

        /**
         * @param PublicKeyInterface $publicKey
         * @param SignatureInterface $signature
         * @param Buffer $messageHash
         * @return bool
         */
        public function verify(PublicKeyInterface $publicKey, SignatureInterface $signature, Buffer $messageHash)
        {
            $publicStr = $publicKey->getBuffer()->getBinary();
            $sigStr = $signature->getBuffer()->getBinary();
            $hashStr = $messageHash->getBinary();

            $ret = \secp256k1_ecdsa_verify($hashStr, $sigStr, $publicStr);
            return (bool)$ret;
        }

        /**
         * @param PrivateKeyInterface $privateKey
         * @param $scalar
         * @return \BitWasp\Bitcoin\Key\PrivateKey
         */
        public function privateKeyMul(PrivateKeyInterface $privateKey, $scalar)
        {
            $privKey = $privateKey->getBuffer()->getBinary(); // mod by reference
            $scalarStr = $this->getBinaryScalar($scalar);

            $ret = \secp256k1_ec_privkey_tweak_mul($privKey, $scalarStr);
            return $this->getRelatedPrivateKey($privateKey, $privKey);
        }

        /**
         * @param PrivateKeyInterface $privateKey
         * @param $scalar
         * @return \BitWasp\Bitcoin\Key\PrivateKey
         */
        public function privateKeyAdd(PrivateKeyInterface $privateKey, $scalar)
        {
            $privKey = $privateKey->getBuffer()->getBinary(); // mod by reference
            $scalarStr = $this->getBinaryScalar($scalar);

            $ret = \secp256k1_ec_privkey_tweak_add($privKey, $scalarStr);
            return $this->getRelatedPrivateKey($privateKey, $privKey);
        }

        /**
         * @param PublicKeyInterface $publicKey
         * @param $scalar
         * @return \BitWasp\Bitcoin\Key\PublicKey
         */
        public function publicKeyAdd(PublicKeyInterface $publicKey, $scalar)
        {
            $pubKey = $publicKey->getBuffer()->getBinary();
            $scalarStr = $this->getBinaryScalar($scalar);

            $ret = \secp256k1_ec_pubkey_tweak_add($pubKey, $scalarStr);
            return $this->getRelatedPublicKey($publicKey, $pubKey);
        }

        /**
         * @param PublicKeyInterface $publicKey
         * @param $scalar
         * @return \BitWasp\Bitcoin\Key\PublicKey
         */
        public function publicKeyMul(PublicKeyInterface $publicKey, $scalar)
        {
            $pubKey = $publicKey->getBuffer()->getBinary();
            $scalarStr = $this->getBinaryScalar($scalar);

            $ret = \secp256k1_ec_pubkey_tweak_mul($pubKey, $scalarStr);
            return $this->getRelatedPublicKey($publicKey, $pubKey);
        }
    }
}
