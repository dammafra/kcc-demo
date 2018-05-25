var os = require('os');

var appRouter = function (app) {
    app.get('' , (req, res) => {
        res.status(200).end('Hello, World!');
    });

    app.get('/hostname', (req, res) => {
        res.status(200).end(os.hostname());
    });
}

module.exports = appRouter;
