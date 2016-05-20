define('templates/helpers/tagTree', ['hbs/handlebars', 'underscore'], function (Handlebars, _) {

    var tags = [];

    function tagTree(context, options) {
        if (_.isEmpty(context)) {
            return;
        }
        iterate(context, '');
        return new Handlebars.SafeString(htmlTags);
    }

    function iterate(obj, stack) {
        for (var property in obj) {
            if (obj.hasOwnProperty(property)) {
                if (typeof obj[property] == "object") {
                    iterate(obj[property], stack + '.' + property);
                } else {
                    if (!_.isEmpty(stack)) {
                        var tag = stack + ' "' + obj[property] + '"';
                        tag = tag.substring(1);
                        tags.push(tag);
                    }
                }
            }
        }
    }

    var htmlTags = '';
    _.each(tagTree, function (val) {
        htmlTags += '<span class="tag">' + val + '</span>';
    });

    Handlebars.registerHelper('tagTree', tagTree);
    
    return tagTree;
});