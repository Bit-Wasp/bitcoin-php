# Types used in the library

The library offers an object oriented interface, so functions are type-hinted where appropriate. 
 
###  Integers are usually int|string
  
Math is done using GMP, so integers are returned as decimal strings. They are the only case a string is not encapsulated by a class.

### Byte strings are Buffers

All strings, such as hashes, serialized data, should be encapsulated as a Buffer. 
 
This class manages the hex/decimal/binary conversions very well, and allows us to deal with binary quite easily.

### Arrays are not returned by public functions

... unless they are a collection of types. Where possible, classes should be used.

