<?php

namespace {
    define("BITCOINCONSENSUS_VERIFY_NONE", 0);
    define("BITCOINCONSENSUS_VERIFY_P2SH", 1 << 0);
    define("BITCOINCONSENSUS_VERIFY_STRICTENC", 1 << 1);
    define("BITCOINCONSENSUS_VERIFY_DERSIG", 1 << 2);
    define("BITCOINCONSENSUS_VERIFY_LOW_S", 1 << 3);
    define("BITCOINCONSENSUS_VERIFY_NULLDUMMY", 1 << 4);
    define("BITCOINCONSENSUS_VERIFY_SIGPUSHONLY", 1 << 5);
    define("BITCOINCONSENSUS_VERIFY_MINIMALDATA", 1 << 6);
    define("BITCOINCONSENSUS_VERIFY_DISCOURAGE_UPGRADABLE_NOPS", 1 << 7);
    define("BITCOINCONSENSUS_VERIFY_CLEANSTACK", 1 << 8);
    define("BITCOINCONSENSUS_VERIFY_CHECKLOCKTIMEVERIFY", 1 << 9);
    define("BITCOINCONSENSUS_VERIFY_WITNESS", 1 << 10);

    /**
     * @return int
     */
    function bitcoinconsensus_version() {
    }

    /**
     * @param string $scriptPubKey
     * @param int $amount
     * @param string $transaction
     * @param int $nInput
     * @param int $flags
     * @param int $error
     * @return bool
     */
    function bitcoinconsensus_verify_script_with_amount($scriptPubKey, $amount, $transaction, $nInput, $flags, &$error) {

    }

    /**
     * @param string $scriptPubKey
     * @param string $transaction
     * @param int $nInput
     * @param int $flags
     * @param int $error
     * @return bool
     */
    function bitcoinconsensus_verify_script($scriptPubKey, $transaction, $nInput, $flags, &$error) {

    }
}

