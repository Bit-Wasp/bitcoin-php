#!/bin/bash

set -e
if [ "$RPC_TEST" = "true" ]; then
    export BITCOIND_PATH="$HOME/bitcoin/bitcoin-$BITCOIN_VERSION/bin/bitcoind";
    vendor/bin/phpunit --debug -c phpunit.rpc.xml;
fi
