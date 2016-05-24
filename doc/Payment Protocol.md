# Payment Protocol

Gavin Andresen's protobuf file was used to generate the protobuf classes in `BitWasp\Bitcoin\PaymentProtocol\Protobufs`
 
The library provides high level wrapper around the protobuf messages for typical operations. 
The Symfony HttpFoundation package is used to create HTTP messages.  

`RequestBuilder`, `RequestSigner`, `PaymentVerifier`, `PaymentHandler` all operate on protobufs. `HttpResponse` can be used
to convert protobufs to a HTTP Response.

### \BitWasp\Bitcoin\PaymentProtocol\RequestBuilder

The `RequestBuilder` is used by servers to generate a payment request. 
Using the class is quite simple: 

```php
$builder = new RequestBuilder();
$builder->setTime(time()); // this is required
/* set other details */
$request = $builder->getPaymentRequest()
```

 `RequestBuilder::setMemo()` - Sets a memo for the payee
 
 `RequestBuilder::setNetwork()` - Sets the network for this request
 
 `RequestBuilder::setOutputs()` - Sets an array of `TransactionOutputInterface` for the request
 
 `RequestBuilder::addOutput()` - Appends a `TransactionOutputInterface` to the request
 
 `RequestBuilder::addAddressPayment()` - Appends an output paying to the provided address
 
 `RequestBuilder::setTime()` - Sets the time for the request **required**
 
 `RequestBuilder::setExpires()` - Set a timestamp when the request should be considered invalid
 
 `RequestBuilder::setMerchantData()` - Arbitrary data for the merchant to identify the request
 
 `RequestBuilder::setSigner()` - Sets a Signer to add a signature for the request
 
 `RequestBuilder::getPaymentDetails()` - Returns a `Protobufs\PaymentDetails` representing the current state
 
 `RequestBuilder::getPaymentRequest()` - Returns a `Protobufs\PaymentRequest` representing the current state


### \BitWasp\Bitcoin\PaymentProtocol\RequestSigner

The `RequestSigner` is initialized by the signature type, and the path to a key and certificate files.
 
The following stating methods can be used for convenience: 

 `RequestSigner::none()` - Return a signer which will not add a signature
 
 `RequestSigner::sha256($keyFile, $certFile)` - Return a signer which will add an x509+sha256 signature
 
 `RequestSigner::sha1($keyFile, $certFile)` - Return a signer which will add an x509+sha251 signature
 
A signature type is not important if you're only verifying a signature. In that case, just use `RequestSigner::none()`. The RequestSigner instance only exposes two methods:

 `RequestSigner::sign($paymentRequest)` - Signs a `Protobuf\PaymentRequest`
 
 `RequestSigner::verify($paymentRequest)` - Validate the signature from `Protobuf\PaymentRequest`

 Verifying a request:
 
```php
$request = new PaymentRequest();
$request->parse('');

$signer = RequestSigner::none();
$signer->verify($request);
```

 Signing a request:
 
```php
$request = new PaymentRequest();

$signer = RequestSigner::sha256('pathToKey', 'pathToCert');
$signer->sign($request);
```
 
 
### \BitWasp\Bitcoin\PaymentProtocol\PaymentVerifier

The `PaymentVerifier` is a simple checker which determines if every output and amount is met at the very least by transactions contained in a `Protobufs\Payment` message. The verifier will determine the cumulative amount paid to an output in both the request, and the list of transactions. If every destination has at least the specified amount, the payment will be accepted. 

If you require stricter checking of `Protobufs\Payment` messages, you should avoid using this class.

It exposes methods for checking a `TransactionCollection`, or a `Protobufs\Payment` against a `Protobufs\PaymentRequest`  

 `PaymentVerifier::checkTransactions($request, $txCollection)` - Check a TransactionCollection against the request
 
 `PaymentVerifier::getTransactions($payment)` - Create a TransactionCollection from the payment
 
 `PaymentVerifier::checkPayment($request, $payment)` - Check a TransactionCollection against the request


### \BitWasp\Bitcoin\PaymentProtocol\PaymentHandler

This class is a factory for a `PaymentACK` message. 

 `PaymentHandler::getPaymentAck($payment, [$memo])` - Create a PaymentACK message
 
 
### \BitWasp\Bitcoin\PaymentProtocol\HttpResponse

The methods in this class accept a message, and produce a HTTP response: 

 `HttpResponse::paymentRequest($request)` - Create a response for the payment request
 `HttpResponse::payment($payment)` - Create a response for the payment
 `HttpResponse::paymentAck($paymentAck)` - Create a response for the paymentACK
  
Sending a PaymentRequest:

```php
$request = new PaymentRequest();
$http = new HttpResponse();
$httpMessage = $http->paymentRequest($request);
$httpMessage->send();
```