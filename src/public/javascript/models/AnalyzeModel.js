define([
    'backbone',
    'collections/ChartCollection',
    'collections/PostCollection'
], function (Backbone, ChartCollection, PostCollection) {

    var AnalyzeModel = Backbone.Model.extend({

        url: '/analyzer',
        parse: function (obj) {

            if (this.get('chart_type') === 'sample') {
                return {
                    'posts': new PostCollection(obj.data)
                };
            }

            return {
                'charts': new ChartCollection(obj.data)
            };
        }

    });

    return AnalyzeModel;
});