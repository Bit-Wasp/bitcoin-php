#!/bin/bash

cat  << EOF
rpcuser=${RPC_TEST_USERNAME}
rpcpassword=${RPC_TEST_PASSWORD}
rpcallowip=127.0.0.1
server=1
daemon=1
regtest=1

EOF
