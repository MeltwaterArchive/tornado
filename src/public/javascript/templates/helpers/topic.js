define('templates/helpers/topic', ['hbs/handlebars', 'underscore'], function (Handlebars, _) {

    function topic(key, context, options) {
        if (_.isEmpty(context)) {
            return;
        }
        var topics = [];
        _.each(context, function (topic) {
            if (!_.isUndefined(topic[key])) {
                topics.push(topic[key]);
            }
        });

        var htmlTags = '';
        _.each(topics,function(val) {
            htmlTags += '<span class="tag">' + val + '</span>';
        });
        return new Handlebars.SafeString(htmlTags);
    }

    Handlebars.registerHelper('topic', topic);
    return topic;
});