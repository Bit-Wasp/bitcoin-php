# Factory Classes

There are a number of factory classes to help create & parse objects. 

An example of a globally set dependency is the Network; if not explicitly provided in a function call, the globally set default will be used.  
 
### \BitWasp\Bitcoin\Bitcoin

   `Bitcoin::getMath()` - Returns a `Math` instance
   
   `Bitcoin::getGenerator()` - Returns a `GeneratorPoint` instance
   
   `Bitcoin::getEcAdapter()` - Returns an `EcAdapterInterface` instance
   
   `Bitcoin::getNetwork()` - Returns the default, or explicitly set `NetworkInterface`
   
   `Bitcoin::setNetwork(NetworkInterface $network)` - Sets $network as the default network
 
### \BitWasp\Bitcoin\Address\AddressFactory:

  `AddressFactory::fromKey(PublicKeyInterface $publicKey)`: Returns an `PayToPubKeyHashAddress` instance for the provided `PublicKeyInterface`
   
  `AddressFactory::fromScript(ScriptInterface $p2shScript)`: Returns an `ScriptHashAddress` instance for the provided `ScriptInterface`
  
  `AddressFactory::fromOutputScript(ScriptInterface $scriptPubKey)`: Attempts to return an Address instance based on the type of scriptPubKey
  
  `AddressFactory::getAssociatedAddress(ScriptInterface $scriptPubKey, [$network])`: Returns a base58 string for a P2PK, or P2PKH script, or P2SH script.
  
  `AddressFactory::fromString($string, [$network])`: Tries to return an AddressInterface based off the string, and network.
   
### \BitWasp\Bitcoin\Block\BlockFactory

  `BlockFactory::fromHex($data)` - Attempts to parse a block from a hex string or Buffer.
  
### \BitWasp\Bitcoin\Block\BlockHeaderFactory

  `BlockHeaderFactory::fromHex()` - Parse a header from a hex string or Buffer
  
### \BitWasp\Bitcoin\Key\PrivateKeyFactory

  `PrivateKeyFactory::fromHex($data)` - Parses `PrivateKeyInterface` from hex string or Buffer
  
  `PrivateKeyFactory::fromInt($int)` - Creates a `PrivateKeyInterface` using the provided decimal integer.
  
  `PrivateKeyFactory::fromWif($wif)` - Parses a base58 encoded private key.
  
  `PrivateKeyFactory::create([$compressed = false]` - Generates a `PrivateKeyInterface`.
  
  `PrivateKeyFactory::generateSecret()` - Generates a Buffer representing a valid private key.

### \BitWasp\Bitcoin\Key\PublicKeyFactory

  `PublicKeyFactory::fromHex($data)` - Parses `PublicKeyInterface` from hex string or Buffer
  
### \BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory

   `HierarchicalKeyFactory::fromExtended($extendedKey)` - Parses a `HierarchicalKey` from a base58 encoded string
   
   `HierarchicalKeyFactory::generateMasterKey()` - Creates a new master `HierarchicalKey`
    
   `HierarchicalKeyFactory::fromEntropy(Buffer $entropy)` - Creates a master `HierarchicalKey` from provided entropy
   
### \BitWasp\Bitcoin\Script\ScriptFactory

   `ScriptFactory::fromHex($data)` - Parses `ScriptInterface` from hex string or Buffer
   
   `ScriptFactory::create([$startScript])` - Returns a `ScriptCreator` instance, used to generate scripts.
   
   `ScriptFactory::sequence($sequenceArray)` - Generates a script given an array consisting of opcodes and Buffers.
    
   `ScriptFactory::scriptPubKey()` - Returns an `OutputScriptFactory` instance, used to safely generate output scripts.
   
   `ScriptFactory::p2sh()` - Returns an `P2shScriptFactory` - an `OutputScriptFactory` augmented to return `P2shScript` instances.
   
   `ScriptFactory::consensus([$flags])` - Returns a `ConsensusInterface` used to validate bitcoin scripts.
   
### \BitWasp\Bitcoin\Script\Factory\ScriptCreator instance:
   This class is used for raw script generation. It's methods mutate the script, which is obtained by
   calling `getScript()` when complete. 
   
   `ScriptCreator::sequence()` - Adds a list of opcodes and Buffer pushes into the script.
    
   `ScriptCreator::op($opName)` - Adds opcode $opName to the script
   
   `ScriptCreator::int()` - Adds an integer value-push into the script
   
   `ScriptCreator::push(Buffer $buffer)` - Adds a value-push of $buffer into the script
   
   `ScriptCreator::concat(ScriptInterface $script)` - Concatenates another `ScriptInterface` to $this
   
   `ScriptCreator::pushSerializable(SerializableInterface $serializable)` - Adds a value-push of a serializable object into the script
   
   `ScriptCreator::pushSerializableArray($serializableArr)` - Adds a vector of serialiable objects
   
   `ScriptCreator::getScript()` - Returns a `ScriptInterface` of the composed script 
   
### \BitWasp\Bitcoin\Script\Factory\OutputScriptFactory instance:
   This class contains methods to safely generate commonly used output scripts.
   
   `OutputScriptFactory::payToAddress(AddressInterface $address)` - Returns output script for the provided address
   
   `OutputScriptFactory::payToPubKey(PublicKeyInterface $publicKey)` - Returns pay-to-pubkey output script for $publicKey.
     
   `OutputScriptFactory::payToPubKeyHash(PublicKeyInterface $publicKey)` - Returns pay-to-pubkey-hash script for $publicKey
   
   `OutputScriptFactory::payToScriptHash(ScriptInterface $p2shScript)` - Returns a pay-to-script-hash script for $p2shScript
   
### \BitWasp\Bitcoin\Script\Factory\P2shScriptFactory instance:
   Returns a P2shScript instance, for the payToPubKey, payToPubKeyHash, and multisig cases. 
   
### \BitWasp\Bitcoin\Signature\SignatureFactory:

   `SignatureFactory::fromHex($data)` - Parses a `SignatureInterface` from a DER signature
   
### \BitWasp\Bitcoin\Signature\TransactionSignatureFactory:

   `TransactionSignatureFactory::fromHex()` - Parses a `TransactionSignatureInterface` given a hex string or Buffer. This is the form with the SigHash byte appended.   

### \BitWasp\Bitcoin\Transaction\TransactionFactory:

   `TransactionFactory::build()` - Returns a `TxBuilder` instance
   
   `TransactionFactory::mutate(TransactionInterface $tx)` - Returns a `TxMutator` instance, used to mutate transactions.
   
   `TransactionFactory::sign(TransactionInterface $tx)` - Returns a `Signer` instance, used to sign transactions.