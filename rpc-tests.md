# RPC tests

A bitcoind executable is required in order to run the
RPC tests. In travis tests we download the version
set in the test environment, and in the local environment
the version can be set with an environment variable.

`BITCOIND_PATH=/path/to/bitcoind vendor/bin/phpunit -c phpunit.rpc.xml`

It can happen that a node can remain running if the tests
crash - keep an eye out for this, and if it happens `killall -9 bitcoind`
works, but make sure real nodes are shut down before hand!


