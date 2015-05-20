<?php

namespace {

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
     * @param string $msg32
     * @param string $privateKey
     * @param string $signature
     * @return int
     */
    function secp256k1_ecdsa_sign($msg32, $privateKey, $signature)
    {
    }

    /**
     * @param string $msg32
     * @param string $privateKey
     * @param string $signature
     * @param int $recid
     * @return int
     */
    function secp256k1_ecdsa_sign_compact($msg32, $privateKey, $signature, $recid)
    {
    }

    /**
     * @param string $msg32
     * @param string $signature
     * @param int $recoveryId
     * @param int $compressed
     * @param string $publicKey
     * @return int
     */
    function secp256k1_ecdsa_recover_compact($msg32, $signature, $recoveryId, $compressed, $publicKey)
    {
    }

    /**
     * @param string $secretKey
     * @param int $compressed
     * @param string $publicKey
     * @return int
     */
    function secp256k1_ec_pubkey_create($secretKey, $compressed, $publicKey)
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
     * @param string $publicKeyLength
     * @param string $tweak
     * @return int
     */
    function secp256k1_ec_pubkey_tweak_mul($publicKey, $tweak)
    {
    }

    /**
     * @param string $publicKey
     * @return int
     */
    function secp256k1_ec_pubkey_verify($publicKey)
    {

    }

    /**
     * @param string $secKey
     * @return int
     */
    function secp256k1_ec_seckey_verify($secKey)
    {
    }
}
