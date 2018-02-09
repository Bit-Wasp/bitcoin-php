#!/bin/bash

set -e
if [ "${PHPUNIT_EXT}" = "true" ]; then
    cd secp256k1-php/secp256k1 && REPORT_EXIT_STATUS=1 make test;
fi
