# Transactions

 The library has classes for the transaction primitives in Bitcoin:
 
## Structures

### OutPoint
 OutPoints wrap a Buffer of the transaction ID, and the vout index that is to be spent. 

[Creating an outpoint](../examples/doc/tx/001_create_outpoint.php)
 
### TransactionInput
 TransactionInputs consist of an OutPoint, the scriptSig, and a sequence number. 
 The $script has a default value of null, where an empty script will be used.  
 The $sequence has a default value of TransactionInput::SEQUENCE_FINAL (0xffffffff)
 
The [TransactionInput](../src/Transaction/TransactionInput.php) class derives it's methods from [TransactionInputInterface](../src/Transaction/TransactionInputInterface.php)

[Creating a TransactionInput](../examples/doc/tx/002_create_txin.php)

### TransactionOutput
The [TransactionOutput](../src/Transaction/TransactionOutput.php) class derives it's methods from [TransactionOutputInterface](../src/Transaction/TransactionOutputInterface.php)

[Creating a TransactionOutput](../examples/doc/tx/003_create_txout.php)

### Transaction
The [Transaction](../src/Transaction/Transaction.php) class derives it's methods from [TransactionInterface](../src/Transaction/TransactionInterface.php)

[Creating a Transaction](../examples/doc/tx/004_create_tx.php)

## Transaction Builder
 The library has a simplified interface for creating transactions. The TransactionBuilder
 is used to create unsigned transactions, or simply to create a transaction from an API response. 
 
 An input can be added to the transaction using:
 
   `TxBuilder::input()` - providing the txid string, and vout index, along with a script (default is empty), and sequence (default is MAX)
   
   `TxBuilder::inputs()` - by providing an array of `TransactionInputInterface`
   
   `TxBuilder::spendOutPoint()` - which takes an OutPoint, and optionally a script and sequence.
   
   `TxBuilder::spendOutPointFrom()` - which takes a transaction, and a vout index, and optionally a script and sequence
  
 An output can be added using:
  `TxBuilder::output()` - providing a value, and a script.
  `TxBuilder::payToAddress()` - providing a value, and an AddressInterface
  `TxBuilder::outputs()` - providing an array of `TransactionOutputInterface`
  
 Witness data can be added using:
  `TxBuilder::witnesses()` - providing an array of `ScriptWitnessInterface` 
  
[Creating a Transaction using TxBuilder](../examples/doc/tx/006_create_tx_txbuilder.php)
 
## Transaction Signer

 Transactions can be signed using `BitWasp\Bitcoin\Transaction\Factory\Signer`
 
 This class handles P2SH and witness transactions or plain output scripts, but only
 if the actual script type is a pay-to-pubkey, pay-to-pubkey-hash, or multisig script. 
 
 The transaction to-be-signed must be passed via the constructor. Signatures will be extracted wherever a full set is found.
  
 Sign is the only method in this class. `$nInput`, `$key`, and `$txOut` are always required. 
 Signing should be viewed as a generic operation, where a known scriptPubKey is solved by a key. 
 In all cases, the key and output script are provided. 
  
 Signing a plain output script (including Witness V0 KeyHash) will not require any additional parameters. 
 Signing a Witness V0 ScriptHash: the witness script must be provided 
 Signing a P2SH output: the redeem script must be provided
 P2SH and Witness scripts can be used together, in which case, the witness & redeem script must both be provided. 
 
 It should be noted that a transaction can *always* be validated, but signing requires code specific to the script type. 
 While the above listed types are supported, others will require modification to the InputSigner.
 
 InputSigners are managed by the Signer, but they have some important responsibilities:
  - Extracting signatures for supported types
  - Signing supported types
  - Re-serializing the scriptSig and scriptWitness fields
 
### Simple output script: pay to pubkey hash
[Spending a public key hash output](../examples/doc/tx/007_sign_p2pkh_tx.php)

### P2SH: 1 of 2 multisig
[Spending a 1-of-2 multisignature (P2SH) output](../examples/doc/tx/008_sign_p2sh_1of2_multisig_tx.php)

### Witness V0 ScriptHash: 2 of 2 multisig
[Spending a 2-of-2 multisignature (P2WSH) output](../examples/doc/tx/009_sign_p2wsh_2of2_multisig_tx.php)

### P2SH V0 Witness Script Hash: 2 of 3 multisig
[Spending a 2-of-3 multisignature (P2SH P2WSH) output](../examples/doc/tx/010_sign_p2sh_p2wsh_2of3_multisig_tx.php)

## Checking Signatures

Signed transactions can be checked, so long as the txOut is known. For non-witness transactions, the amount does not have to be known, but it's better to keep it available.  

Checking signatures is done using the script interpreter. Since there exist multiple bindings to choose from, `ScriptFactory::consensus()`
will always return the most suitable. 

The example below validates a transaction produced in the `P2SH | Witness V0 Script Hash: 1 of 2 multisig` example.

### Checking a P2SH|P2WSH 2 of 3 multisig

[Verifying the signature on a fully signed transaction input](../examples/doc/tx/011_verify_p2sh_p2wsh_2of3_multisig_tx.php)
