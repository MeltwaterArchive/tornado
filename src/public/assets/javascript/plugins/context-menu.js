define(['jquery', 'underscore', 'mustache'],
function($, _, Mustache) {
    /**
     * Context Menu
     *
     * # Usage
     * 1. Add a `data-context-menu` attribute to the element you want to trigger
     *    the context menu. Its value should be of the form `contextMenuName:text`
     *    (example: data-context-menu="explore:Explore in new worksheet").
     *    You can also have multiple context menu items by using a comma delimiter.
     * 2. Done.
     */
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Context Menu
     *
     * @singleton
     */
    var ContextMenu = function() {
        this.el = {
            trigger: '[data-context-menu]',
            contextMenu: '.context-menu'
        };

        this.classes = {
            contextMenuActive: 'context-menu--active',
            contextMenuItem: 'context-menu__item'
        };

        this.attributes = {
            origin: 'data-context-menu-origin',
            contextMenuData: 'data-context-menu'
        };

        this.template = '[data-tornado-template="context-menu"]';
        this.position = {};
        this.$origin = null;
        this.data = {
            menus: []
        };

        this.bindEvents();
    };

    ContextMenu.prototype.bindEvents = function() {
        var _this = this;

        $('body').on('contextmenu.contextmenu', this.el.trigger, function(ev) {
            ev.preventDefault();

            _this.prepareContextMenu(ev);
        });

        $('body').on('hide.contextmenu', function() {
            _this.hide();
        });

        $('body').on('click.contextmenu', function(ev) {
            var $target = $(ev.target);

            if (!$target.hasClass(_this.classes.contextMenuActive) &&
                !$target.hasClass(_this.classes.contextMenuItem)) {
                _this.hide();
            }
        });
    };

    /**
     * Create and position the context menu
     *
     * @param  {Object} ev contextMenu event object
     * @return {Object}    Context Menu instance
     */
    ContextMenu.prototype.prepareContextMenu = function(ev) {
        this.clear();

        var contextMenuData;

        this.$origin = $(ev.target);
        this.position.left = ev.pageX;
        this.position.top = ev.pageY - $(window).scrollTop();

        contextMenuData = this.$origin.attr(this.attributes.contextMenuData);

        this.$origin.attr(this.attributes.origin, '');

        this
            .processData(contextMenuData)
            .render();

        return this;
    };

    /**
     * Construct the data required to render the context menu.
     * The unprocessed data comes from the origin's data attribute.
     *
     * @param  {String} data Raw data
     * @return {Object}      Context Menu instance
     */
    ContextMenu.prototype.processData = function(data) {
        // explore:Explore in new worksheet
        var menus = data.split(',');

        _.each(menus, function(menu) {
            var menuDataArray = menu.split(':');

            this.data.menus.push({
                name: menuDataArray[0],
                text: menuDataArray[1]
            });
        }.bind(this));

        return this;
    };

    /**
     * Returns the element that triggered the context menu
     *
     * @return {jQuery} jQuery object
     */
    ContextMenu.prototype.getOrigin = function() {
        return this.$origin;
    };

    ContextMenu.prototype.show = function() {
        $(this.el.contextMenu).addClass(this.classes.contextMenuActive);

        return this;
    };

    ContextMenu.prototype.hide = function() {
        if (!_.isNull(this.$origin)) {
            this.$origin.removeAttr(this.attributes.origin);
        }

        $(this.el.contextMenu).removeClass(this.classes.contextMenuActive);

        return this;
    };

    /**
     * Reset all the instance data
     *
     * @return {Object} Context Menu instance
     */
    ContextMenu.prototype.clear = function() {
        this.hide();

        this.data.menus = [];
        this.position = {};
        this.$origin = null;

        $(this.el.contextMenu).remove();

        return this;
    };

    ContextMenu.prototype.render = function() {
        var template = $(this.template).html();
        template = Mustache.render(template, this.data);

        $('body').append(template);
        $(this.el.contextMenu).css(this.position);

        setTimeout(function() {
            this.show();
        }.bind(this), 0);
    };

    instance = new ContextMenu();

    return instance;
});
