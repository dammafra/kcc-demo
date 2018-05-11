var express = require('express');
var bodyParser = require('body-parser');
var app = express();

app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

var config = require('/etc/cfg/config.js');
var routes = require('./routes.js')(app);

var server = app.listen(config.port, () => console.log('Listening on port %s ...', server.address().port));
