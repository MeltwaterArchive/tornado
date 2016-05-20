define([
    'jquery',
    'underscore',
    'backbone',
    'mustache',
    'spinner',
    'models/menu',
    'hbs!templates/workbook/main-menu',
    'views/sidebar/SidebarView'
], function($, _, Backbone, Mustache, Spinner, Menu, MainMenuTpl, SidebarView) {

    'use strict';

    var MenuView = Backbone.View.extend({
        'el': '[data-tornado-view="main-menu"]',

        events: {
            'click li a': 'click'
        },

        template: MainMenuTpl,

        initialize: function () {
            this.model.on('change:controller', this.render.bind(this));
            this.model.on('change:worksheet', this.render.bind(this));
            this.switching = false;
        },

        click: function (evt) {

            if (this.switching === true) {
                return false;
            }

            var $item = $(evt.target).parent('li');

            // if we have selected the highlighted one don't do anything
            if ($item.hasClass('main-menu__item--active')) {
                // stop the event bubbling up
                evt.stopPropagation();
                // also stop it from loading the link
                evt.preventDefault();
                return;
            }

            // enable the switching toggle so we can't click anywhere else
            this.switching = true;

            // pop out the pane and set it to loading
            SidebarView.loadingStart(function () {
                this.switching = false;
            }.bind(this));
        },

        render: function (controller) {
            // render the template
            var template = this.template(this.model.attributes);
            // add it to the content
            this.$el.html(template);
            // toggle it so everyone can see
            this.show();
            // select the correct item
            this.$el.find('[data-main-menu-item="' + this.model.get('controller') + '"]').addClass('main-menu__item--active');
            // add the class to toggle the timeseries button
            var worksheetType = this.model.get('worksheet').chart_type || 'default';
            if (this.model.get('workbook') && this.model.get('workbook').status == 'archived') {
                worksheetType = 'locked';
            }

            this.$el.attr('data-main-menu-worksheet-type', worksheetType);

            if (this.model.get('controller') === 'worksheet') {
                // overview doesn't cause a subtree rerender
                this.switching = false;
            }

            return this;
        },

        show: function () {
            this.$el.removeClass('main-menu--disabled');
            this.model.set('visible', true);
            return this;
        },

        hide: function () {
            this.$el.addClass('main-menu--disabled');
            this.model.set('visible', false);
            return this;
        }

    });

    return MenuView;
});
