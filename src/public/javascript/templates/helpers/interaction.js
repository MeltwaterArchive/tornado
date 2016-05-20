define('templates/helpers/interaction', ['hbs/handlebars'], function (Handlebars) {
    function interaction(context, options) {
    	if (context) {
    		return new Handlebars.SafeString(twemoji.parse(context));
    	}
    	return context;
    }

    Handlebars.registerHelper('interaction', interaction);
    return interaction;
});