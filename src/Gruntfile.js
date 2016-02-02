module.exports = function(grunt) {
	// load all grunt tasks matching the ['grunt-*', '@*/grunt-*'] patterns 
	require('load-grunt-tasks')(grunt);
	// measures the time each task takes
	//require('time-grunt')(grunt);
	// load the config for the tasks
	require('load-grunt-config')(grunt);
};