module.exports = {
    dev: {
        options: {
            outputStyle: 'nested',
            sourcemap: true
        },
        files: [{
            expand: true,
            cwd: 'public/assets/scss',
            src: 'tornado.scss',
            dest: 'public/assets/css',
            ext: '.css'
        }, {
            expand: true,
            cwd: 'public/assets/scss/skins',
            src: '*.scss',
            dest: 'public/assets/css/skins',
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
            cwd: 'public/assets/scss',
            src: 'tornado.scss',
            dest: 'public/assets/css',
            ext: '.css'
        }, {
            expand: true,
            cwd: 'public/assets/scss/skins',
            src: '*.scss',
            dest: 'public/assets/css/skins',
            ext: '.css'
        }]
    }
};