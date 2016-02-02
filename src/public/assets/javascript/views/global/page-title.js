define(['jquery', 'underscore', 'mustache'],
function($, _, Mustache) {
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Page Title view
     *
     * @singleton
     */
    var PageTitleView = function() {
        this.collection = [];

        // Custom data passed to the view
        this.data = {
            projectName: '',
            workbookName: ''
        };

        this.template = '[data-tornado-template="page-title"]';
        this.el = '[data-tornado-view="page-title"]';
    };

    PageTitleView.prototype.update = function(data) {
        this.data = $.extend({}, this.data, data);

        this.render();
    };

    PageTitleView.prototype.render = function() {
        var template = $(this.template).html();
        template = Mustache.render(template, this.data);

        $(this.el).html(template);

        return this;
    };

    instance = new PageTitleView();

    return instance;
});
