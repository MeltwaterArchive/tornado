define(['templates/helpers/hasMenuOption'], function (hasMenuOption) {

    var menuOptionExpectations = {
        'sample': [
            {'name': 'overview', 'expectation': true},
            {'name': 'filters', 'expectation': true},
            {'name': 'timespan', 'expectation': false},
            {'name': 'comparison', 'expectation': false},
            {'name': 'dimensions', 'expectation': false}
        ],
        'timeSeries': [
            {'name': 'overview', 'expectation': true},
            {'name': 'filters', 'expectation': true},
            {'name': 'timespan', 'expectation': true},
            {'name': 'comparison', 'expectation': true},
            {'name': 'dimensions', 'expectation': false}
        ],
        'freqDist': [
            {'name': 'overview', 'expectation': true},
            {'name': 'filters', 'expectation': true},
            {'name': 'timespan', 'expectation': false},
            {'name': 'comparison', 'expectation': true},
            {'name': 'dimensions', 'expectation': true}
        ],
        'locked': [
            {'name': 'overview', 'expectation': true},
            {'name': 'filters', 'expectation': false},
            {'name': 'timespan', 'expectation': false},
            {'name': 'comparison', 'expectation': false},
            {'name': 'dimensions', 'expectation': false}
        ]
    };


    var HasMenuOptionTest = function () {

        _.each(menuOptionExpectations, function (menuOptions, chartType) {
            test('MenuOptions::' + chartType, function () {
                _.each(menuOptions, function (option) {
                    equal(hasMenuOption(chartType, option['name']), option['expectation'], 'has overview');
                });
            });
        });
    };

    return HasMenuOptionTest;
});