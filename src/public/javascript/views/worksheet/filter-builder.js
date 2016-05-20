define(['backbone','underscore'], function (Backbone, _) {

    var FilterBuilder = {

        filtersInfo: function (filters) {
            var sets = [];

            _.each(['country', 'region', 'gender', 'age'], function (key) {
                if (!_.isArray(filters[key]) || !filters[key].length) {
                    return;
                }

                var values = _.map(filters[key], _.capitalize);
                var lastValue = values.length > 1 ? values.pop() : null;
                var str = values.join(', ');
                sets.push(lastValue ? str + ' and ' + lastValue : str);
            });

            if (filters.keywords && filters.keywords.length) {
                sets.push('that contains any of the words "' + filters.keywords.join('", "') + '"');
            }

            if (filters.links && filters.links.length) {
                sets.push('linking to "' + filters.links.join('", "') + '"');
            }

            var filtersInfo = sets.join(', ');

            if (filters.csdl && filters.csdl.length) {
                filtersInfo += (filtersInfo.length) ? ' and' : '';
                filtersInfo += ' custom CSDL';
            }

            var start = filters.start ? new Date(filters.start * 1000) : null;
            var end = filters.end ? new Date(filters.end * 1000) : null;
            var startDate = start ? start.toLocaleDateString() + ' ' + start.toLocaleTimeString() : null;
            var endDate = end ? end.toLocaleDateString() + ' ' + start.toLocaleTimeString() : null;

            if (start && end) {
                filtersInfo += ' posted between ' + startDate + ' and ' + endDate;
            } else if (start) {
                filtersInfo += ' posted since ' + startDate;
            } else if (end) {
                filtersInfo += ' posted before ' + endDate;
            }

            return filtersInfo.length ? _.capitalize(filtersInfo.trim()) + '.' : false;
        }

    };

    return FilterBuilder;
});