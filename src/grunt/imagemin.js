module.exports = {
	dynamic: {
		options: {
			optimizationLevel: 3,
			svgoPlugins: [{ removeViewBox: false }]
		},
		files: [{
			expand: true,
			cwd: 'public/images',
			src: ['**/*.*'],
			dest: 'public/<%= buildLocation %>/images'
		}]
	}
};