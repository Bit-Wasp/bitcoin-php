var bitcore = require('bitcore')
//script = require('bitcore/script')
var fs = require('fs')

var fileName = process.argv[2];

fs.readFile(fileName, 'utf8', function (err,data) {
  if (err) {
    return console.log(err);
  }
  var lines = data.split('\n');
  try {
  scriptPubkey = new bitcore.Script(lines[0]);
  tx = bitcore.Transaction(lines[1]);
  nIn = parseInt(lines[2]);
  var flags = bitcore.Script.Interpreter.SCRIPT_VERIFY_P2SH | bitcore.Script.Interpreter.SCRIPT_VERIFY_DERSIG
  //var flags = 0
  var interpreter = bitcore.Script.Interpreter();
  var verified = interpreter.verify(tx.inputs[nIn].script, scriptPubkey, tx, nIn);
  var stack = interpreter.stack
  if (verified) {
      console.log(1);
  } else {
      console.log(0);
      console.log(interpreter.errstr)
  }
  } catch(err) {
      console.log(0);
      return
  }

});
