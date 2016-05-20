module.exports = {
	requirejs: {
		compile: {
			options: {
				baseUrl: 'assets/javascript',
				mainConfigFile: 'assets/javascript/requireconfig.js',
				done: function(done, output) {
					var duplicates = require('rjs-build-analysis').duplicates(output);

					if (Object.keys(duplicates).length) {
						grunt.log.subhead('Duplicates found in requirejs build:');
						grunt.log.warn(duplicates);
						return done(new Error('r.js built duplicate modules, please check the excludes option.'));
					}

					done();
				}
			}
		}
	}
};