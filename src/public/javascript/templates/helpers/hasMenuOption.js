define('templates/helpers/hasMenuOption', ['hbs/handlebars', 'underscore'], function (Handlebars, _) {

    function hasMenuOption(chartType, menuOption, options) {

        var showOption = false;
        switch (chartType) {
            case 'sample':
                showOption = _.includes(['overview', 'filters'], menuOption);
                break;
            case 'timeSeries':
                showOption = _.includes(['overview', 'timespan', 'comparison', 'filters'], menuOption);
                break;
            case 'freqDist':
                showOption = _.includes(['overview', 'dimensions', 'comparison', 'filters'], menuOption);
                break;
            case 'locked':
                showOption = _.includes(['overview'], menuOption);
                break;
        }

        if (_.isUndefined(options)) {
            return showOption;
        }

        if (showOption) {
            return options.fn(this);
        }
        return options.inverse(this);
    }

    Handlebars.registerHelper('hasMenuOption', hasMenuOption);
    return hasMenuOption;
});