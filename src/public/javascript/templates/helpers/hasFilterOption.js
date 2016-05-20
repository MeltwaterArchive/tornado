define('templates/helpers/hasFilterOption', ['hbs/handlebars', 'underscore'], function (Handlebars, _) {

    function hasFilterOption(chartType, menuOption, options) {

        var showOption = false;
        var allFilterOptions = ['timeframe', 'keywords', 'country', 'region', 'gender', 'links', 'age-range', 'csdl'];
        switch (chartType) {
            case 'sample':
                var available = _.without(allFilterOptions, 'country', 'region', 'age-range', 'gender');
                showOption = _.includes(available, menuOption);
                break;
            case 'timeSeries':
                showOption = _.includes(allFilterOptions, menuOption);
                break;
            case 'freqDist':
                showOption = _.includes(allFilterOptions, menuOption);
                break;
            case 'locked':
                showOption = _.includes(allFilterOptions, menuOption);
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

    Handlebars.registerHelper('hasFilterOption', hasFilterOption);
    return hasFilterOption;
});