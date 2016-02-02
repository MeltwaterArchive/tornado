module.exports = {
	dev: {
        root: 'public',
        port: 8080,
        runInBackground: true,
        logFn: function () {
            // send everything to dev:null
        }
    }
}