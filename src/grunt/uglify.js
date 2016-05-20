module.exports = {
	'dev': {
		files: [{
			expand: true,
			cwd: "public<%= buildLocation %>/javascript",
			src: [
				"**/*.js",
				"!**/test/**/*.js"
			],
			dest: 'public<%= buildLocation %>/javascript'
		}]
	}
};