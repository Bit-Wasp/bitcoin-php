v1.0.0
=======

The v1.0.0 version is finally being released, marking the 
libraries departure from the 'in development' v0.0.x series.
It probably should have happened a long time ago.

It includes a number of BC breaks in addition to the new PHP
version requirement.

Compatibility Notice
====================

## PHP version

v1.0.0 raises the minimum PHP version to 7.0. v0.0.35.x is 
the last series of versions to support PHP5.6 & HHVM.

## ext-secp256k1

The minimum version of `ext-secp256k1` is now v0.2.x, as the
parameter order of some functions was changed to be more in
line with upstream.

About https://github.com/Bit-Wasp/secp256k1-php:

The secp256k1 PHP extension exposes the libsecp256k1 C library
used by Bitcoin Core for ECC operations. If the extension is
installed, bitcoin-php will automatically use it as the default
`EcAdapterInterface` as it massively improves performance.

## ext-bitcoinconsensus

The minimum version of `ext-bitcoinconsensus` is now v3.0.x.

About https://github.com/Bit-Wasp/bitcoinconsensus-php:

The bitcoinconsensus PHP extension exposes the minimal C library
provided by bitcoin core in signed builds, and when compiling on
your own machine. It's recommended to install this if you're doing
anything related to signature verification with the library.

Notable Changes
===============

Backwards compatibility breaks:

 - #545 - KeyInterface::getAddress is removed, convert via KeyToScriptHelper
 - #587 - Fix DASH HD bytes
 - #581 - extracts script parsing logic for qualifying a spk, rs, ws against
   eachother.
 - #583 - `Network` implementation details have changed. Rewrote how various 
   network attributes are stored in the class.
 - [173469f] Require Buffer in XXXSerializer::parse() methods, and extract
   'fromBuffer' for PrivateKeyFactory, PublicKeyFactory. 'fromHex' methods
   now exclusively accept hex strings.
 - #596 - AddressFactory extract major functions into AddressCreator instance
   and remove old class entirely. 
 - #597 - MessageSigner: require NetworkInterface to be passed in order to 
   sign and verify.
 - #600 - Make HierarchicalKey class immutable by removing 'toPublic', which 
   would convert the object to the public key. 'withoutPrivateKey' returns 
   a distinct object which is public. 
 - #601 - OutputScriptFactory: remove payToAddress. This code was dangerous
 - #602 - Remove unused function ScriptCreator::pushSerializable
 - #619 - Use M addresses for litecoin prefixes
 - #604 - Remove BIP70 classes, code moved to an outside project (bip70/bip70-php)
 - #662 - Classes for KeyToScript conversions, and introduce one of these (mandatory)
   in HierarchicalKey.
 - #670 - Correct viacoin bech32 prefixes
 - #671 - PrivateKeyFactory, PublicKeyFactory, ElectrumKeyFactory,
   and HierarchicalKeyFactory are now class instances, as they
   should wrap an EcAdapterInterface. Static calls should be replaced
   with instance calls.
 - #694 - Fixes litecoin testnet prefix for P2SH addresses
 - #702 - Remove static bech32 classes in favor of `bitwasp/bech32`
 - #740 - HierarchicalKey: Don't automatically increment the index if
   an invalid key is produced (per the bip 1:2^128 chance of occurring)
   doing this can cause inconsistent derivations for multisig applications,
   make developers deal with it.
 - #743 - Bip39: ensure BIP compatibility by ensuring all mnemonics adhere to
   the BIP's standards.
 - #744 - Remove functions in HierarchicalKeySequences, and add more specific
   ones which perform strict validation. 
   HierarchicalKey: removes deriveFromlist
   Rewrites MultisigHD to bring it in line with HierarchicalKey, so it
   also supports zpubs/Ypubs, etc, and generating addresses/script data.
 - #752 - Remove BitcoinCashChecker, and Uri::setAmountBtc now accepts a string
   not a float.
 - #755 - PrivateKeyFactory, remove old methods, add specific methods for
   working with compressed and uncompressed keys. 
 - #760 - ProofOfWork bc break, don't throw exception on valid input (but invalid for bits). Rename `check` to `checkPow` in case devs miss it

General features:
 - #593 - Allow custom Checker implementations in Signer by introducing CheckerCreator
 - #671 - Add `ElectrumKey::withoutPrivateKey`
 - #673 - Deal with networks with different SIGNED_MESSAGE_PREFIX lengths, field is varint
 - #684 - Allow dealing with networks where addresses have multi-byte base58 prefixes
 - #523 - Optional support for 'zero' values in multsignature scriptSigs and witnesses
   indicating signer index. (Signer::padUnsignedMultisigs($setting))
 - #525 - An extremely experimental and not perfect feature: support for complicated 
   output script types, with limited support for existing script templates within
   arbitrarily nested IF/NOTIF opcodes. (Signer::allowComplexScripts($setting))
 - #556 - don't touch branches the signer didn't look at

Testing:
 - #745 - Adopted phpstan for CI builds, will slowly work on this over time
 - #750 - Migrate to newer scrutinizer analysis tool for PHP7+

Credits
=======

A special thanks to everyone who contributed directly to the release:

 - afk11
 - DaShak
 - evadogstar
 - hauptmedia
 - Max
 - murich
 - romanornr
 - rubensayshi
 - samnela
 - Vasiliy-Bondarenko

