<?php

namespace {
    
    /**
     * @param string $msg32
     * @param string $signature
     * @param integer $signatureLength
     * @param string $privateKey
     * @return int
     */
    function secp256k1_ecdsa_sign($msg32, $signature, $signatureLength, $privateKey)
    {
    }

    /**
     * @param string $msg32
     * @param string $signature
     * @param string $publicKey
     * @return int
     */
    function secp256k1_ecdsa_verify($msg32, $signature, $publicKey)
    {
    }

    /**
     * @param string $privateKey
     * @param string $tweak
     * @return int
     */
    function secp256k1_ec_privkey_tweak_add($privateKey, $tweak)
    {
    }

    /**
     * @param string $privateKey
     * @param string $tweak
     * @return int
     */
    function secp256k1_ec_privkey_tweak_mul($privateKey, $tweak)
    {
    }

    /**
     * @param string $publicKey
     * @param string $tweak
     * @return int
     */
    function secp256k1_ec_pubkey_tweak_add($publicKey, $tweak)
    {
    }

    /**
     * @param string $publicKey
     * @param string $pubkeyLen
     * @param string $tweak
     * @return int
     */
    function secp256k1_ec_pubkey_tweak_mul($publicKey, $pubkeyLen, $tweak)
    {
    }
}
