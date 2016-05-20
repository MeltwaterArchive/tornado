define('templates/helpers/prettyDate', ['hbs/handlebars', 'underscore', 'moment'],
    function (Handlebars, _, moment) {

        function prettyDate(context, options) {
            if (_.isEmpty(context)) {
                return;
            }
            return moment(context, "D MMM, h:mma Z").format("D MMM, YYYY h:mm a");
        }

        Handlebars.registerHelper('prettyDate', prettyDate);
        return prettyDate;
    });