define(['jquery', 'underscore', 'blocker'],
function($, _, Blocker) {
    /**
     * Loader
     *
     * Creates a loading state by adding an overlay
     * to target elements and preventing any action.
     *
     * ## Usage
     * Add a `data-loader` attribute to the element you want to
     * trigger the loading state. The value of the data attribute
     * is of this type: `blocker-element-selector:text` and is comma
     * delimited to allow adding multiple blocker elements. On click,
     * a div element is created and appended to the target blocker element.
     * If there's `text` set, it creates a button with the defined text and
     * a spinner, and then it's appended to the blocker element.
     *
     * ## Example
     * # HTML
     * <button
     *     data-dimension-build-button
     *     data-loader="[data-tornado-view='page-content']:Building,[data-tornado-view='page-sidebar']"
     *     type="button"
     *     class="dimension-build-button"
     *     disabled>Build chart
     * </button>
     *
     * This will append blocker elements to two elements:
     * a) `[data-tornado-view='page-content']` which will also contain a button with the
     * text `Building` and a spinner
     * b) `[data-tornado-view='page-sidebar']` with no buttons
     *
     */

    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Loader
     *
     * @singleton
     */
    var Loader = function() {
        this.el = {
            // The element that triggers the loader.
            // As a value it contains the element selectors
            // that are the loader blocker containers.
            loader: '[data-loader]'
        };

        this.attributes = {
            loader: 'data-loader',

            // Attribute added to the loader element when loading
            // to prevent clicking again on the same loader element
            loaderLoading: 'data-loader-loading'
        };

        this.events = {
            click: 'click.loader'
        };

        this.bindEvents();
    };

    Loader.prototype.bindEvents = function() {
        var _this = this;

        $('body').on(this.events.click, this.el.loader, function() {
            var $loader = $(this);

            if (_.isUndefined($loader.attr(_this.attributes.loaderLoading))) {
                _this.load($loader);
            }
        });
    };

    Loader.prototype.unbindEvents = function() {
        $('body').off('.loader');
    };

    /**
     * Blocks the content to show the user there's
     * a request in the background
     *
     * @param  {jQuery} $loader jQuery element that triggers the loader
     * @return {Object}         Loader instance
     */
    Loader.prototype.load = function($loader) {
        var loaderContainers = this.getLoaderContainers($loader);

        _.each(loaderContainers, function(loaderContainer) {
            var $loaderContainer = $(loaderContainer.split(':')[0]);
            var loaderMessage = loaderContainer.split(':')[1];

            Blocker.block($loaderContainer, {
                text: loaderMessage,
                attributes: {
                    'data-spinner': 'spin'
                }
            });
        }.bind(this));

        $loader.attr(this.attributes.loaderLoading, '');

        return this;
    };

    Loader.prototype.showLoader = function ($container, message) {
         Blocker.block($container, {
                text: message,
                attributes: {
                    'data-spinner': 'spin'
                }
            });
    };

    /**
     * Get an array of the loader blocker containers
     * and the button text associated with each (optional)
     *
     * @param  {jQuery}          $loader jQuery element that triggers the loader
     * @return {selector[:text]} blocker Element selector where the blocker will
     *                                   be appended and optional button text
     */
    Loader.prototype.getLoaderContainers = function($loader) {
        return $loader
            .attr(this.attributes.loader)
            .split(',');
    };

    /**
     * Remove the loader blockers
     * @param  {jQuery} $loader Original element that triggered the loader
     * @return {Object}         Loader instance
     */
    Loader.prototype.unload = function($loader) {
        // Target a specific blocker container or all of them
        var loaderContainers = this.getLoaderContainers($loader);

        _.each(loaderContainers, function(loaderContainer) {
            var $loaderContainer = $(loaderContainer.split(':')[0]);

            Blocker.unblock($loaderContainer.find('[data-blocker]'));
        }.bind(this));

        $loader.removeAttr(this.attributes.loaderLoading);

        return this;
    };

    instance = new Loader();

    return instance;
});
