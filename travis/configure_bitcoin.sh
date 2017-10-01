#!/bin/bash

cat  << EOF
rpcuser=${TEST_RPC_USERNAME}
rpcpassword=${TEST_RPC_PASSWORD}
rpcallowip=127.0.0.1
server=1
daemon=1
regtest=1
EOF
