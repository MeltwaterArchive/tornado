define('templates/helpers/hashtag', ['hbs/handlebars', 'underscore'], function (Handlebars, _) {

    function hashtag(context, options) {
        if (_.isEmpty(context)) {
            return;
        }

        var htmlTags = '';
        _.each(context,function(val) {
            htmlTags += '<span class="tag">' + val + '</span>';
        });
        return new Handlebars.SafeString(htmlTags);
    }

    Handlebars.registerHelper('hashtag', hashtag);
    return hashtag;
});