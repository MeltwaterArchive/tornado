/**
 * Recording state management Module.
 *
 * Possible actions which can be done on Recording:
 *  - pause
 *  - resume
 *
 * @param {jQuery}
 * @param {ErrorFormatter}
 */
require(['jquery', 'responseFormatter'], function($, responseFormatter) {
    'use strict';

    /**
     * RecordingState class that changes a Recording state by calling the API actions.
     *
     * @constructor
     */
    var RecordingState = function() {
        this.el = {
            stateBtn: '[data-recording-state]'
        };
        this.attributes = {
            action: 'data-recording-state'
        };

        this.$el = null;

        this.pauseAction = 'pause';
        this.resumeAction = 'resume';

        this.initialize();
    };

    /**
     * Registers all event listeners and makes an initial configuration
     */
    RecordingState.prototype.initialize = function() {
        var that = this;

        // no binding this object due to keeping reference to "clicked" button
        $(this.el.stateBtn).on('click', function (ev) {
            ev.preventDefault();

            that.perform(
                $(this).attr(that.attributes.action),
                $(this).attr('href')
            );
        });
    };

    /**
     * Calls given action
     *
     * @param {String} action
     * @param {String} url
     */
    RecordingState.prototype.perform = function(action, url) {
        switch(action) {
            case this.pauseAction:
                this.call(url, 'PUT');
                break;
            case this.resumeAction:
                this.call(url, 'PUT');
                break;
            default:
                console.error('Unsupported action: ' + action);
        }
    };

    /**
     * Performs a recording action request and handles a server response
     *
     * @param {String} url
     * @param {String} httpMethod
     */
    RecordingState.prototype.call = function(url, httpMethod) {
        $.ajax({
            url: url,
            type: httpMethod,
            success: function(response) {
                if (response.meta && response.meta.redirect_uri) {
                    window.location.href = response.meta.redirect_uri;
                } else {
                    console.warn('missing redirect_uri metadata in response.');
                }
            },
            error: function(jqXHR) {
                responseFormatter.format(jqXHR);
            }.bind(this)
        });
    };

    return new RecordingState();
});
