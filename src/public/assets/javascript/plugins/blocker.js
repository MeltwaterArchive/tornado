define(['jquery', 'underscore'],
function($, _) {
    /**
     * Blocker
     *
     * Blocks interaction in certain elements using
     * a nice overlay and an optional message.
     *
     * Supports 2 message types:
     * Default: Green background - usually for loading states
     * Error: Red background - used for showing errors
     */

    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Blocker
     *
     * @singleton
     */
    var Blocker = function() {
        this.el = {
            // The element that is appended to the blocker containers
            blocker: '[data-blocker]',

            // The [optional] button that is appended to the blocker
            blockerButton: '[data-blocker-button]'
        };

        this.attributes = {
            blocker: 'data-blocker',
            blockerButton: 'data-blocker-button'
        };

        this.type = {
            default: 'default',
            error: 'error'
        }

        this.actions = {
            block: 'block'
        }

        this.events = {
            block: 'block.blocker',
            unblock: 'unblock.blocker',
            unblockAll: 'unblockall.blocker'
        };

        this.bindEvents();
    };

    Blocker.prototype.bindEvents = function() {
        var _this = this;

        $('body').on(this.events.block, function(ev, data) {
            this.block(data.$container, data.message);
        }.bind(this));

        $('body').on(this.events.unblock, function(ev, data) {
            this.unblock(data.$blocker);
        }.bind(this));

        $('body').on(this.events.unblockAll, function() {
            this.unblockAll();
        }.bind(this));
    };

    Blocker.prototype.unbindEvents = function() {
        $('body').off('.blocker');
    };

    /**
     * Constructs and appends the blocker elements to the defined containers
     *
     * @param  {jQuery} $container  $Element where the blocker will be
     *                              appended optional button text
     * @param  {Object} message     Optional message to show.
     *                              Contains `text` and `attributes` (optional).
     * @return {Object}             Blocker instance
     */
    Blocker.prototype.block = function($container, message) {
        var $blocker = $('<div>');

        $blocker
            .attr(this.attributes.blocker, '')
            .appendTo($container);

        // Sometimes, we just want the overlay
        if (!_.isUndefined(message.text)) {
            this.createMessageButton($blocker, message);
        }

        // Allow a few extra browser ticks before
        // we make the block elements visible
        setTimeout(function() {
            $blocker.attr(this.attributes.blocker, this.actions.block);
        }.bind(this), 10);

        return this;
    };

    Blocker.prototype.createMessageButton = function($blocker, message) {
        var $button;
        var buttonAttributes;

        // Position the button in the middle of the window height
        var buttonTopPosition = (window.innerHeight - $blocker.offset().top) / 2;

        // If the container is actually smaller than the window height,
        // position it in the middle of the container
        if (($blocker.offset().top + $blocker.height()) <= window.innerHeight) {
            buttonTopPosition = $blocker.height() / 2;
        }

        // Default message button attributes
        buttonAttributes = {
            'data-blocker-button': '',
            style: 'top: ' + buttonTopPosition + 'px',
            type: 'button',
            text: message.text
        };

        if (!_.isUndefined(message.attributes)) {
            buttonAttributes = _.extend(buttonAttributes, message.attributes);
        }

        $button = $('<button>', buttonAttributes);

        $blocker.append($button);
    };

    /**
     * Remove a blocker
     *
     * @return {Object} Blocker instance
     */
    Blocker.prototype.unblock = function($blocker) {
        $blocker.attr(this.attributes.blocker, '');

        // Giving the $blocker some time to fadeout first, then remove it
        setTimeout(function() {
            $blocker.remove();
        }, 400);

        return this;
    };

    Blocker.prototype.unblockAll = function() {
        _.each($(this.el.blocker), function(blocker) {
            this.unblock($(blocker));
        }.bind(this));
    };

    instance = new Blocker();

    return instance;
});
