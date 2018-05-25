var os = require('os');

var USERNAME = 'kcc-user';
var PASSWORD = 'password';

var appRouter = function (app) {
    app.get('' , (req, res) => {
        res.status(200).end('Hello, K8s!');
    });

    app.get('/hostname', (req, res) => {
        res.status(200).end(os.hostname());
    });

    app.post('/auth', (req, res) => {
        var username = req.body.username;
        var password = req.body.password;

        if (username === USERNAME && password === PASSWORD)
            res.status(200).end('Authenticated');
        else
            res.status(403).end('Autenthication failed');
    });
}

module.exports = appRouter;
