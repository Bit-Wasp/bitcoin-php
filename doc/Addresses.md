Addresses
==========



### \BitWasp\Bitcoin\Address\BaseAddressCreator:

The BaseAddressCreator abstract class defines the basic contract of an AddressCreator.

Implementations must fulfill the following two methods:
 
  `$addrCreator->fromOutputScript(ScriptInterface $scriptPubKey): AddressInterface`: Attempts to return an Address instance based on the type of scriptPubKey
  
  `$addrCreator->fromString($string, [$network]): AddressInterface`: Tries to return an AddressInterface based off the string, and network.
   
The default implementation for the library is `AddressCreator`, which currently supports base58 and bech32 addresses.

