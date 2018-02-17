#!/bin/bash
set -x
cd $(git rev-parse --show-toplevel)
if [ "$PHPUNIT_EXT" = "true" ]; then
    EXT_PHP='-dextension="secp256k1.so" -dextension="bitcoinconsensus.so"' make phpunit-ci;
elif [ "$PHPUNIT" = "true" ]; then
    make phpunit-ci;
fi

