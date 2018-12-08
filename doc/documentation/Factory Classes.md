# Factory Classes

There are a number of factory classes to help create & parse objects. 

An example of a globally set dependency is the Network; if not explicitly provided in a function call, the globally set default will be used.  
 
### \BitWasp\Bitcoin\Bitcoin

   `Bitcoin::getMath()` - Returns a `Math` instance
   
   `Bitcoin::getGenerator()` - Returns a `GeneratorPoint` instance
   
   `Bitcoin::getEcAdapter()` - Returns an `EcAdapterInterface` instance
   
   `Bitcoin::getNetwork()` - Returns the default, or explicitly set `NetworkInterface`
   
   `Bitcoin::setNetwork(NetworkInterface $network)` - Sets $network as the default network

### \BitWasp\Bitcoin\Block\BlockFactory

  `BlockFactory::fromHex($data)` - Attempts to parse a block from a hex string or Buffer.
  
### \BitWasp\Bitcoin\Block\BlockHeaderFactory

  `BlockHeaderFactory::fromHex()` - Parse a header from a hex string or Buffer
  
### \BitWasp\Bitcoin\Key\PrivateKeyFactory

Private key factory is an instance, which wraps an EcAdapterInterface (optional constructor arg)

  `PrivateKeyFactory::fromHexCompressed($hexString)` - Parses `PrivateKeyInterface` from hex string
  `PrivateKeyFactory::fromHexUncompressed($hexString)` - Parses `PrivateKeyInterface` from hex string
  `PrivateKeyFactory::fromBufferCompressed($data)` - Parses `PrivateKeyInterface` from a Buffer
  `PrivateKeyFactory::fromBufferUncompressed($data)` - Parses `PrivateKeyInterface` from a Buffer
  `PrivateKeyFactory::generateCompressed(new Random())` - Generates a new compressed private key using the RNG
  `PrivateKeyFactory::generateUncompressed(new Random())` - Generates a new uncompressed private key using the RNG   
  `PrivateKeyFactory::fromWif($wif)` - Parses a base58 encoded private key.

### \BitWasp\Bitcoin\Key\PublicKeyFactory

Public key factory is an instance, which wraps an EcAdapterInterface (optional constructor arg)

  `PublicKeyFactory::fromHex($data)` - Parses `PublicKeyInterface` from hex string or Buffer
  
### \BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory

HierarchicalKeyFactory is an instance. It can be initialized with a special Base58ExtendedKeySerializer
with knowledge about SLIP132 prefixes to work with zpubs/etc

   `HierarchicalKeyFactory::fromExtended($extendedKey)` - Parses a `HierarchicalKey` from a base58 encoded string. Takes an optional second parameter, the Network object containing bip32 network prefixes. Set this explicitly, or override the default with Bitcoin::setNetwork()
   
   `HierarchicalKeyFactory::generateMasterKey($random)` - Creates a new master `HierarchicalKey`. Takes an optional second parameter, the ScriptDataFactory responsible for producing addresses
    
   `HierarchicalKeyFactory::fromEntropy(Buffer $entropy)` - Creates a master `HierarchicalKey` from provided entropy. Takes an optional second parameter, the ScriptDataFactory responsible for producing addresses
   
   `HierarchicalKeyFactory::multisig($scriptDataFactory, ...$extendedKeys)` - Creates a multisignature HD account from the HD keys

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
   This class is intended contains methods to safely generate commonly used output scripts.
   
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
