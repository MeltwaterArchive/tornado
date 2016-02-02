define([
    'jquery', 
    'underscore', 
    'blocker', 
    'spinner'
], function($, _, Blocker, Spinner) {
    /**
     * Buzzkill - The bearer of bad news
     *
     * Handles errors like a pro.
     *
     * Types of errors:
     * - Alert:     Typically after an analyzer request for specific errors.
     *              Makes use of the `blocker` plugin to put an overlay on a
     *              specific container and show the response error.
     * - Notice:    Generic, app-wide errors. Shown at the bottom of the viewport.
     */

    'use strict';

    /**
     * Buzzkill
     *
     * @singleton
     */
    var Buzzkill = function() {
        this.el = {
            alert: '.buzzkill--alert',
            notice: '.buzzkill--notice',

            // Data attribute appended to `[data-form-field]`
            // elements which hosts the error message of that field
            fieldError: '[data-buzzkill-form-field-error]'
        };

        this.classes = {
            active: 'buzzkill--active'
        };

        this.attributes = {
            fieldError: 'data-buzzkill-form-field-error'
        };

        this.events = {
            alert: 'alert.buzzkill',
            notice: 'notice.buzzkill',
            form: 'form.buzzkill',
            close: 'click.buzzkill'
        };

        this.bindEvents();
    };

    Buzzkill.prototype.bindEvents = function() {
        var _this = this;

        $('body').on(this.events.alert, function(ev, data) {
            this.alert(data.$container, data.message);
        }.bind(this));

        $('body').on(this.events.close, this.el.alert, function() {
            _this.clearAlert($(this).closest('[data-blocker]'));
        });

        $('body').on(this.events.notice, function(ev, data) {
            this.notice(data.message);
        }.bind(this));

        $('body').on(this.events.close, this.el.notice, function() {
            this.clearNotice();
        }.bind(this));
    };

    Buzzkill.prototype.unbindEvents = function() {
        $('body').off('.buzzkill');
    };

    /**
     * Create an error alert
     *
     * @param  {jQuery} $alertContainer     jQuery container $element to append the alert
     * @param  {Object} errorMessage        Error message
     * @return {Object}                     Buzzkill instance
     */
    Buzzkill.prototype.alert = function($alertContainer, errorMessage) {
        Spinner.stopAll();

        Blocker.block($alertContainer, {
            text: errorMessage,
            attributes: {
                class: 'buzzkill buzzkill--alert'
            }
        });
    };

    Buzzkill.prototype.clearAlert = function($alertContainer) {
        Blocker.unblock($alertContainer);
    };

    Buzzkill.prototype.notice = function(errorMessage, type) {
        var $notice = $('<div>', {
            text: errorMessage,
            class: ['buzzkill', 'buzzkill--notice', type].join(' ')
        });

        $notice.appendTo('body');

        setTimeout(function() {
            $notice.addClass(this.classes.active);
        }.bind(this), 10);
    };

    Buzzkill.prototype.clearNotice = function() {
        $(this.el.notice).removeClass(this.classes.active);

        setTimeout(function() {
            $(this.el.notice).remove();
        }.bind(this), 500);
    };

    Buzzkill.prototype.form = function($form, meta) {
        Spinner.stopAll();

        _.each(meta, function(errorMessage, inputName) {
            var $inputErrorHolder = $form.find('[data-form-field="' + inputName + '"]');

            $inputErrorHolder.attr('data-buzzkill-form-field-error', errorMessage);
        });

        setTimeout(function() {
            $form.addClass(this.classes.active);
        }.bind(this), 50);
    };

    Buzzkill.prototype.clearForm = function($form) {
        $form.removeClass(this.classes.active);

        _.each($(this.el.fieldError), function(field) {
            $(field).removeAttr(this.attributes.fieldError);
        }.bind(this));
    };

    return new Buzzkill();
});
