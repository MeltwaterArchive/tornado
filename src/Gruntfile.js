var params = require('./config/parameters.yml');

module.exports = function(grunt) {
	// measures the time each task takes
	require('time-grunt')(grunt);
	// load the config for the tasks
	require('load-grunt-config')(grunt, {
		data: {
			'buildLocation': params.parameters['cs.build.location']
		}
	});
};