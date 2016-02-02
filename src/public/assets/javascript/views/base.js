define(['jquery', 'backbone', 'mustache', 'spinner', 'loader', 'tooltip', 'contextmenu', 'modallica', 'views/global/menu', 'views/global/page-title', 'plugins/dropdown-toggle'],
function($, Backbone, Mustache, Spinner, Loader, Tooltip, ContextMenu, Modallica, Menu, PageTitle, DropdownToggle) {
    'use strict';

    /**
     * BaseView constructor
     *
     * @description This is the generic view that all the
     *              other views extend.
     */
    var BaseView = function(data) {
        // Custom data to pass to the view
        this.data = data || {};

        this.spinner = Spinner;
        this.loader = Loader;
        this.modallica = Modallica;
        this.menu = Menu;
        this.pageTitle = PageTitle;
        this.contextMenu = ContextMenu;
        this.tooltip = Tooltip;
        this.dropdownToggle = DropdownToggle;

        this.$body = $('body');
    };

    BaseView.prototype.finalizeView = function() {
        var _this = this;

        // Inform all previous views on this container element that they should
        // clean up after themselves (e.g. unbind any events).
        if (this.el) {
            // But prevent this event from bubbling up,
            // so the parent events don't accidentally remove themselves
            $(this.el).one('viewdestroy', function(ev) {
                ev.stopPropagation();
            });

            $(this.el).trigger('viewdestroy');

            // Also, listen for this event in the future
            $(this.el).one('viewdestroy', function(ev) {
                _this.unbindEvents();
            });
        }

        this
            .unbindEvents()
            .bindEvents();
    };

    BaseView.extend = Backbone.View.extend;

    return BaseView;
});
