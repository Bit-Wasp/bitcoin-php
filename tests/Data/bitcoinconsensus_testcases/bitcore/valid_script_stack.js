var lib = require('./valid_script_lib.js');
var fileName = process.argv[2];

lib.stack(fileName, function(data) {console.log(data);})
