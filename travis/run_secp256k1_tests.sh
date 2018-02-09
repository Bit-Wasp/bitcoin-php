#!/bin/bash

if [ "${PHPUNIT_EXT}" = "true" ]; then
    cd secp256k1-php/secp256k1;
    REPORT_EXIT_STATUS=1 make test;
    if [ $? != "0" ]; then
        find tests -type f -iname "*.log" -exec cat {} +;
        exit 1;
    fi;
fi

exit 0
