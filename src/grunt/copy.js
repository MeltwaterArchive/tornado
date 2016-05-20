module.exports = {
	main: {
		files: [{
			expand: true,
			cwd: 'public/javascript',
			src: '**/*.*',
			dest: 'public/<%= buildLocation %>/javascript'
		},{
			expand: true,
			cwd: 'public/sass',
			src: '**/*.*',
			dest: 'public/<%= buildLocation %>/sass'
		},{
			expand: true,
			cwd: 'public/bower',
			src: '**/*.*',
			dest: 'public/<%= buildLocation %>/bower'
		}
		]
	}
};