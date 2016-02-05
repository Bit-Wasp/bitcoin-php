# Serialization

Classes which implement the SerializableInterface expose methods related to serialization. 

 - getBuffer(): This method returns the binary representation of the class as a Buffer. 
 - getHex(): Returns the serialized form, but in hex encoding. 
 - getInt(): Where the number is an unsigned integer, getInt can convert the number to a big-num decimal. 
 - getBinary(): Returns the object serialized, as a byte string.
 
## Serializers

Objects which implement SerializableInterface *usually* have a serializer also capable of parsing.  

Serializers expose three main methods:

 - fromParser(Parser $parser) - attempt to extract the structure from the provided parser
 - parse() - attempt to parse the data: allows hex, or Buffer's
 - serialize($object) - converts the object into a Buffer
  
