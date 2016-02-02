define([
    'jquery', 
    'underscore', 
    'backbone',
    'mustache', 
    'spinner',
    'models/menu'
], function($, _, Backbone, Mustache, Spinner, Menu) {
    'use strict';




    /**
     * Main menu view
     *
     * @singleton
   */
    var MenuView = function() {
        this.collection = [];

        // Custom data passed to the view
        this.data = {
            project: null,
            worksheet: null,
            view: ''
        };

        this.template = '[data-tornado-template="main-menu"]';

        this.el = {
            menu: '[data-tornado-view="main-menu"]',
            item: '[data-main-menu-item]',
            itemLink: '[data-main-menu-link]'
        };

        this.classes = {
            menuDisabled: 'main-menu--disabled',
            itemActive: 'main-menu__item--active'
        };

        this.attributes = {
            item: 'data-main-menu-item',
            itemLink: 'data-main-menu-link',
            worksheetType: 'data-main-menu-worksheet-type'
        };

        this.rendered = false;
    };

    MenuView.prototype.bindEvents = function() {

        $(this.el.menu).on('click.menu', this.el.item, function(ev) {
            if ($(this).hasClass(this.classes.itemActive)) {
                return false;
            }
            this.highlight($(this));
        }.bind(this));

        $('body').on('update.menu', function(ev, data) {
            this.update(data);
        }.bind(this));

        return this;
    };

    MenuView.prototype.unbindEvents = function() {
        $(this.el.menu).off('.menu');
        return this;
    };

    MenuView.prototype.hide = function() {
        $(this.el.menu).addClass(this.classes.menuDisabled);
    };

    MenuView.prototype.show = function() {
        $(this.el.menu).removeClass(this.classes.menuDisabled);
    };

    MenuView.prototype.stopSpinner = function() {
        //Spinner.stopAll($('[data-tornado-view="main-menu"]'));
        return this;
    };

    MenuView.prototype.highlight = function(menuItem) {
        var $activeItem = $('[' + this.attributes.item + '="' + menuItem + '"]');

        $(this.el.item).removeClass(this.classes.itemActive);
        $activeItem.addClass(this.classes.itemActive);
    };

    MenuView.prototype.update = function(data) {
        this.data = $.extend({}, this.data, data);

        if (_.isNull(this.data.worksheet)) {
            return this;
        }

        if (this.rendered === false) {
            this.render();
        }

        this
            .updateLinks()
            .setChartType();

        if (!_.isUndefined(data.view)) {
            this.highlight(data.view);
        }
    };

    /*
     * Each chart type has different menu items available.
     * We're setting the type to handle showing/hiding those
     * menu items using CSS.
     */
     
    MenuView.prototype.setChartType = function() {
        $(this.el.menu).attr(this.attributes.worksheetType, this.data.worksheet.chart_type || 'default');

        return this;
    };

    MenuView.prototype.updateLinks = function() {
        var _getUpdatedLink = function($link) {
            return $link.attr(this.attributes.itemLink)
                .replace('{projectId}', this.data.project.id)
                .replace('{worksheetId}', this.data.worksheet.id);
        }.bind(this);

        _.each($(this.el.itemLink), function(link) {
            var $link = $(link);
            var updatedLink = _getUpdatedLink($link);

            $link.attr('href', updatedLink);
        }.bind(this));

        return this;
    };

    MenuView.prototype.render = function() {
        var template = $(this.template).html();
        template = Mustache.render(template, this.data);

        $(this.el.menu).html(template);
        this.show();

        this.rendered = true;

        this
            .setChartType()
            .unbindEvents()
            .bindEvents();

        return this;
    };

    return new MenuView();
});
