module.exports = {
    dev: {
        options: {
            outputStyle: 'nested',
            sourcemap: true
        },
        files: [{
            expand: true,
            cwd: 'public/sass',
            src: 'tornado.scss',
            dest: 'public/css',
            ext: '.css'
        }, {
            expand: true,
            cwd: 'public/sass/skins',
            src: '*.scss',
            dest: 'public/css/skins',
            ext: '.css'
        }]
    },
    dist: {
        options: {
            outputStyle: 'compressed',
            sourcemap: false
        },
        files: [{
            expand: true,
            cwd: 'public<%= buildLocation %>/sass',
            src: 'tornado.scss',
            dest: 'public<%= buildLocation %>/css',
            ext: '.css'
        }, {
            expand: true,
            cwd: 'public<%= buildLocation %>/sass/skins',
            src: '*.scss',
            dest: 'public<%= buildLocation %>/css/skins',
            ext: '.css'
        }]
    }
};