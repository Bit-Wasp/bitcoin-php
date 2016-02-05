  
##Bitcoin
=======
[![Build Status](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/badges/build.png?b=master)](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Bit-Wasp/bitcoin-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Bit-Wasp/bitcoin-php/?branch=master)

[![Latest Stable Version](https://poser.pugx.org/bitwasp/bitcoin/v/stable.png)](https://packagist.org/packages/bitwasp/bitcoin)
[![Join the chat at https://gitter.im/Bit-Wasp/bitcoin-php](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/Bit-Wasp/bitcoin-php?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

  This repository contains an implementation of Bitcoin using mostly pure PHP.

  As the saying goes - "never use a dynamically interpreted language to write consensus software"
  Now that you know this, please continue!

  If the relevant extensions are installed, ie, secp256k1-php and bitcoinconsensus-php,
  it will be used instead of the native PHP implementation.

  Besides classes for interfacing with Bitcoind's RPC, and Stratum servers, this
  library does NOT contain any code to interact with the network, or any API's.

  See below, or [here](https://github.com/Bit-Wasp/bitcoin-php) for examples.

  Other repositories which are part of this project:
  - [Secp256k1 PHP extension](https://github.com/Bit-Wasp/secp256k1-php)
  - [Bitcoinconsensus PHP extension](https://github.com/Bit-Wasp/bitcoinconsensus-php)
  - [Buffertools library](https://github.com/Bit-Wasp/buffertools-php) - a library for dealing with binary data
  - [Stratum client library](https://github.com/Bit-Wasp/stratum-php) - a library for connecting to Stratum servers (uses ReactPHP)
  - [Bitcoin P2P PHP](https://github.com/Bit-Wasp/bitcoin-p2p-php) - a library that speaks the Bitcoin protocol (uses ReactPHP)
  - [Testing package](https://github.com/Bit-Wasp/testing-php) - a composer package to pull CI and development tools

## Installation
You can install this library via Composer: `composer require bitwasp/bitcoin`

## Contributing

 All contributions are welcome. Please see [[this page](https://github.com/Bit-Wasp/bitcoin-php/blob/master/CONTRIBUTING.md)] before you get started

## Documentation

 Check out the beginnings of the documentation for the library: [[Introduction](doc/Introduction.md)]

## Presently supported:

 - Bloom filters
 - Blocks, headers, and merkle blocks.
 - Regular/P2SH scripts.
 - An adaptable elliptic-curve library, using [[PhpEcc](https://github.com/mdanter/phpecc)] by default, or libsecp256k1 if the bindings are found.
 - Support for building, parsing, signing/validating transactions.
 - Deterministic signatures (RFC6979)
 - BIP32 and electrum (older type I) deterministic key algorithms.
 - ScriptFactory for common input/output types, parser, interpreter, and classifiers.
 - Supports bindings to libbitcoinconsensus.
 - RPC bindings to Bitcoin Core's RPC
 - Bindings to Stratum (electrum) servers
 - Easy serialization to binary representation of most classes
 - SIGHASH types when creating transactions
 - Payment Protocol (BIP70)
 - Blockchain classes utilizing the doctrine/cache package
