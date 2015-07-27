<?php

namespace {

    /**
     * @param int $flags
     * @return resource
     */
    function secp256k1_context_create($flags)
    {
    }

    /**
     * @param resource $context
     * @return bool
     */
    function secp256k1_context_destroy($context)
    {
    }

    /**
     * @param resource $context
     * @return resource
     */
    function secp256k1_context_clone($context)
    {
    }

    /**
     * @param $context
     * @return void
     */
    function secp256k1_context_randomize($context)
    {
    }

    /**
     * @param resource $context
     * @param string $msg32
     * @param string $signature
     * @param string $publicKey
     * @return int
     */
    function secp256k1_ecdsa_verify($context, $msg32, $signature, $publicKey)
    {
    }

    /**
     * @param resource $context
     * @param string $msg32
     * @param string $privateKey
     * @param string $signature
     * @return int
     */
    function secp256k1_ecdsa_sign($context, $msg32, $privateKey, $signature)
    {
    }

    /**
     * @param resource $context
     * @param string $msg32
     * @param string $privateKey
     * @param string $signature
     * @param int $recid
     * @return int
     */
    function secp256k1_ecdsa_sign_compact($context, $msg32, $privateKey, $signature, $recid)
    {
    }

    /**
     * @param resource $context
     * @param string $msg32
     * @param string $signature
     * @param int $recoveryId
     * @param int $compressed
     * @param string $publicKey
     * @return int
     */
    function secp256k1_ecdsa_recover_compact($context, $msg32, $signature, $recoveryId, $compressed, $publicKey)
    {
    }

    /**
     * @param resource $context
     * @param string $secretKey
     * @param int $compressed
     * @param string $publicKey
     * @return int
     */
    function secp256k1_ec_pubkey_create($context, $secretKey, $compressed, $publicKey)
    {
    }

    /**
     * @param resource $context
     * @param string $privateKey
     * @param string $tweak
     * @return int
     */
    function secp256k1_ec_privkey_tweak_add($context, $privateKey, $tweak)
    {
    }

    /**
     * @param resource $context
     * @param string $privateKey
     * @param string $tweak
     * @return int
     */
    function secp256k1_ec_privkey_tweak_mul($context, $privateKey, $tweak)
    {
    }

    /**
     * @param resource $context
     * @param string $publicKey
     * @param string $tweak
     * @return int
     */
    function secp256k1_ec_pubkey_tweak_add($context, $publicKey, $tweak)
    {
    }

    /**
     * @param resource $context
     * @param string $publicKey
     * @param string $tweak
     * @return int
     */
    function secp256k1_ec_pubkey_tweak_mul($context, $publicKey, $tweak)
    {
    }

    /**
     * @param resource $context
     * @param string $publicKey
     * @return int
     */
    function secp256k1_ec_pubkey_verify($context, $publicKey)
    {

    }

    /**
     * @param resource $context
     * @param string $secKey
     * @return int
     */
    function secp256k1_ec_seckey_verify($context, $secKey)
    {
    }
}
