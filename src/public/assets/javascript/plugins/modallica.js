define(['jquery', 'underscore', 'mustache', 'spinner'],
function($, _, Mustache, Spinner) {
    /**
     * Modallica - Master of modals is pulling the strings
     */

    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Modallica
     *
     * @singleton
     */
    var Modallica = function() {
        this.el = {
            trigger: '[data-modallica]',
            wrapper: '[data-modallica-wrapper]',
            modallica: '.modallica',
            contentWrapper: '[data-modallica-content-wrapper]',
            expandable: '[data-modallica-expandable]',
            confirmationCheckbox: '[data-modallica-expandable] input[type="checkbox"]',
            actions: {
                submit: '[data-modallica-action="submit"]',
                expand: '[data-modallica-action="expand"]',
                close: '[data-modallica-action="close"]'
            }
        };

        this.classes = {
            active: 'modallica-wrapper--active',
            expanded: 'modallica--expanded',
            confirmed: 'modallica--confirmed'
        };

        this.attributes = {
            modallica: 'data-modallica',
            templateName: 'data-modallica-template-name',
            action: 'data-modallica-action'
        };

        this.events = {
            click: 'click.modallica',
            ready: 'ready.modallica',
            typing: 'keyup.modallica',
            change: 'change.modallica',
            hide: 'hide.modallica'
        };

        this.template = '[data-tornado-template="modallica"]';

        this.bindEvents();
    };

    Modallica.prototype.bindEvents = function() {
        var _this = this;

        // Show event
        $('body').on(this.events.click, this.el.trigger, function(ev) {
            _this.show($(this));
        });

        // Close events
        $('body').on(this.events.click, this.el.actions.close, function(ev) {
            ev.preventDefault();

            _this.hide();
        });

        $('body').on(this.events.click, this.el.wrapper, function(ev) {
            if ($(ev.target).hasClass(_this.classes.active)) {
                _this.hide();
            }
        });

        $('body').on(this.events.typing, function(ev) {
            if (ev.keyCode === 27 && $(_this.el.wrapper).hasClass(_this.classes.active)) {
                _this.hide();
            }
        });

        $('body').on(this.events.hide, function() {
            _this.hide();
        });

        // Expand the modal for confirmation
        $('body').on(this.events.click, this.el.actions.expand, function(ev) {
            ev.preventDefault();

            _this.toggleExpand();
        });

        // Event fired when hitting the confirmation checkbox (if any)
        // The confirmation checkbox is always in the expandable container
        $('body').on(this.events.change, this.el.confirmationCheckbox, function() {
            _this.toggleConfirmation($(this).prop('checked'));
        });
    };

    Modallica.prototype.unbindEvents = function() {
        $('body').off('.modallica');
    };

    Modallica.prototype.getTemplateName = function() {
        return $(this.el.modallica).attr(this.attributes.templateName);
    };

    /**
     * Processes the modal data from element that triggered it and renders it
     *
     * @param  {jQuery} $trigger The element that triggered the modal
     * @return {Object}          Modallica instance
     */
    Modallica.prototype.show = function($trigger) {
        var newModalDataArray = $trigger.attr(this.attributes.modallica).split(':');
        var newModalData = {
            title: newModalDataArray[0],
            templateName: newModalDataArray[1]
        };

        this.showFromData(newModalData);

        return this;
    };

    /**
     * Shows the modal based on the passed modal data.
     *
     * @param  {Object} modalData Must contain `title` and `templateName`.
     * @return {Object}           Modallica instance
     */
    Modallica.prototype.showFromData = function(modalData) {
        $(this.el.wrapper).remove();

        this.render(modalData);

        return this;
    };

    /**
     * Hide the modal and remove it from the DOM
     * Also triggers a custom event so we can hook
     * custom actions on trigger.
     *
     * @return {Object} Modallica instance
     */
    Modallica.prototype.hide = function() {
        var templateName = this.getTemplateName();

        $(this.el.wrapper).removeClass(this.classes.active);

        setTimeout(function() {
            $(this.el.wrapper).remove();

            $('body').trigger(templateName + ':' + this.events.hide);
        }.bind(this), 300);

        return this;
    };

    /**
     * Expand the modal for confirmation (if applicable)
     *
     * @return {Object} Modallica instance
     */
    Modallica.prototype.toggleExpand = function() {
        if ($(this.el.modallica).hasClass(this.classes.expanded)) {
            $(this.el.modallica).removeClass(this.classes.expanded);
        } else {
            $(this.el.modallica).addClass(this.classes.expanded);
        }

        return this;
    };

    /**
     * Toggles the confirmation class on our modal depending on
     * the `checked` prop of the confirmation checkbox, to trigger
     * the necessary style changes.
     *
     * @param  {Boolean} state Confirmation checkbox value
     * @return {Object}        Modallica instance
     */
    Modallica.prototype.toggleConfirmation = function(state) {
        if (state) {
            $(this.el.modallica).addClass(this.classes.confirmed);
        } else {
            $(this.el.modallica).removeClass(this.classes.confirmed);
        }

        return this;
    };

    /**
     * Renders the main content of the modal, after rendering
     * the modal and the header. Also, fires a custom event in
     * case we want to execute any methods after everything is
     * rendered.
     *
     * @param  {String} templateName The name of the modal's template
     * @param  {Object} modalData    Modal data. Must contain `title`
     *                               and `templateName` keys. Passed to
     *                               templates.
     * @return {Object}              Modallica instance
     */
    Modallica.prototype.renderContent = function(templateName, modalData) {
        var template = $('[data-tornado-template="' + templateName + '"]').html();
        template = Mustache.render(template, modalData);

        $(this.el.contentWrapper).html(template);
        $(document).trigger(templateName + ':' + this.events.ready);

        setTimeout(function() {
            $(this.el.wrapper).addClass(this.classes.active);
        }.bind(this), 10);

        return this;
    };

    /**
     * Renders the modal shell and the header
     *
     * @param  {Object} modalData Modal data. Must contain `title`
     *                            and `templateName` keys. Passed to
     *                            templates.
     * @return {Object}           Modallica instance
     */
    Modallica.prototype.render = function(modalData) {
        var template = $(this.template).html();
        template = Mustache.render(template, modalData);

        $('body').append(template);

        this.renderContent(modalData.templateName, modalData);

        return this;
    };

    instance = new Modallica();

    return instance;
});
