define(['jquery', 'underscore', 'hbs!templates/worksheet/sample', 'views/base'],
    function ($, _, SampleTpl, View) {
        'use strict';

        var SampleView = View.extend({
            // The DOM element where the charts will be rendered
            el: '[data-tornado-view="page-content"]',

            interactions: [],

            bindEvents: function () {
                return this;
            },

            unbindEvents: function () {
                return this;
            },

            /**
             * Render a sample of super public interactions
             */
            render: function () {
                $(this.el).html(SampleTpl({'posts': this.data.posts}));
                this.finalizeView();
            }
        });

        return SampleView;
    });
