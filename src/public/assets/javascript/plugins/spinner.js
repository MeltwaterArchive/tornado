define(['jquery', 'underscore'],
function($, _) {
    /**
     * Spinner
     *
     * Adds a loading spinner to elements
     */

    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Spinner
     *
     * @singleton
     */
    var Spinner = function() {
        this.el = {
            spinner: '[data-spinner]'
        };

        this.attributes = {
            spinner: 'data-spinner'
        };

        this.actions = {
            spin: 'spin'
        };

        this.events = {
            click: 'click.spinner'
        };

        this.bindEvents();
    };

    Spinner.prototype.bindEvents = function() {
        var _this = this;

        $('body').on(this.events.click, this.el.spinner, function(ev) {
            _this.spin($(this));
        });
    };

    Spinner.prototype.unload = function() {
        $('body').off(this.events.click, this.el.spinner);
    };

    /**
     * Start spinner on element
     *
     * @param  {jQuert} $el jQuery element
     * @return {Object}     Spinner instance
     */
    Spinner.prototype.spin = function($el) {
        $el.attr(this.attributes.spinner, this.actions.spin);

        return this;
    };

    /**
     * Stop spinner
     *
     * @param  {jQuert} $el jQuery element
     * @return {Object}     Spinner instance
     */
    Spinner.prototype.stop = function($el) {
        $el.attr(this.attributes.spinner, '');

        return this;
    };

    Spinner.prototype.stopAll = function($container) {
        var _this = this;

        if (!!$container) {
            _.each($container.find(this.el.spinner), function(spinner) {
                $(spinner).attr(_this.attributes.spinner, '');
            });
        } else {
            _.each($(this.el.spinner), function(spinner) {
                $(spinner).attr(_this.attributes.spinner, '');
            });
        }

        return this;
    };

    instance = new Spinner();

    return instance;
});
