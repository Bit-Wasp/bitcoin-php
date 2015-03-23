<?php

namespace {
    
    /**
     * @param $msg32
     * @param $signature
     * @param $signatureLength
     * @param $privateKey
     * @return int
     */
    function secp256k1_ecdsa_sign($msg32, $signature, $signatureLength, $privateKey)
    {
    }

    /**
     * @param $msg32
     * @param $signature
     * @param $publicKey
     * @return int
     */
    function secp256k1_ecdsa_verify($msg32, $signature, $publicKey)
    {
    }

    /**
     * @param $privateKey
     * @param $tweak
     * @return int
     */
    function secp256k1_ec_privkey_tweak_add($privateKey, $tweak)
    {
    }

    /**
     * @param $privateKey
     * @param $tweak
     * @return int
     */
    function secp256k1_ec_privkey_tweak_mul($privateKey, $tweak)
    {
    }

    /**
     * @param $publicKey
     * @param $tweak
     * @return int
     */
    function secp256k1_ec_pubkey_tweak_add($publicKey, $tweak)
    {
    }

    /**
     * @param $publicKey
     * @param $tweak
     * @return int
     */
    function secp256k1_ec_pubkey_tweak_mul($publicKey, $tweak)
    {
    }
}
