define(['jquery', 'mustache', 'views/base', 'views/dimensions/dimensions-draggaball'], function($, Mustache, View, DimensionsDraggaball) {
    'use strict';

    /**
     * Renders the dimensions template
     *
     * @param {data} data Worksheet model
     */
    var DimensionsView = View.extend({
        el: '[data-tornado-view="page-content"]',
        headerEl: '[data-tornado-view="page-header"]',
        footerEl: '[data-tornado-view="page-footer"]',
        template: '[data-tornado-template="dimensions"]',

        dimensionBuildButton: '[data-dimension-build-button]',
        onSubmitDimensionsCallback: $.noop,

        bindEvents: function() {
            var _this = this;

            $(this.el).on('click.dimensions', this.dimensionBuildButton, function(ev) {
                ev.preventDefault();

                var value = _this.draggaball.getValue();
                var dimensions = [value.x];

                if (value.y) {
                    dimensions.push(value.y);
                }

                if (value.z) {
                    dimensions.push(value.z);
                }

                _this.submitDimensions(dimensions);
            });

            return this;
        },

        unbindEvents: function() {
            $(this.el).off('.dimensions', this.dimensionBuildButton);

            return this;
        },

        onSubmitDimensions: function(callback) {
            this.onSubmitDimensionsCallback = callback;
        },

        submitDimensions: function(dimensions) {
            this.onSubmitDimensionsCallback(dimensions);
        },

        render: function() {
            this.data.cancelLink = '/projects/' + this.data.projectId + '/worksheet/' + this.data.worksheet.id;

            var template = $(this.template).html();
            template = Mustache.render(template, this.data);

            $(this.el).html(template);
            $(this.headerEl).html('');
            $(this.footerEl).html('');

            this.draggaball = new DimensionsDraggaball({
                disabled: {
                    x: false,
                    y: (this.data.worksheet.chart_type === 'histogram'),
                    z: false
                }
            });

            var selectedDimensions = this.data.selectedDimensions || [];

            // a histogram chart uses dimensions in the x & z dimensions, y is 
            // diasabled, this results in the z being undefined so we are 
            // shuffling around the dimensions
            if (this.data.worksheet.chart_type === 'histogram') {
                this.draggaball.setValue({
                    x: selectedDimensions[0] || null,
                    y: null,
                    z: selectedDimensions[1] || null
                });
            } else {
                this.draggaball.setValue({
                    x: selectedDimensions[0] || null,
                    y: selectedDimensions[1] || null,
                    z: selectedDimensions[2] || null
                });
            }

            this.finalizeView();
        }
    });

    return DimensionsView;
});
