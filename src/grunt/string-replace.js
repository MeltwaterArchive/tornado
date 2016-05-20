module.exports = {
    inline: {
        files: {
            'public<%= buildLocation %>/javascript/requireconfig.js': 'public/<%= buildLocation %>/javascript/requireconfig.js',
            'public<%= buildLocation %>/sass/_base.scss': 'public/<%= buildLocation %>/sass/_base.scss'
        },
        options: {
            replacements: [{
                pattern: /(urlArgs: ).+/ig,
                replacement: function () {
                    return 'urlArgs: "' + Math.round(Date.now()/1000) + '",';
                }
            }, {
                pattern: /(\$image-path: "\/images")/g,
                replacement: '$image-path: "<%= buildLocation %>/images"'
            }]
        }
    }
};
