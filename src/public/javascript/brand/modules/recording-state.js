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
        this.pauseText = 'Pause';
        this.resumeAction = 'resume';
        this.resumeText = 'Resume';

        this.initialize();
    };

    /**
     * Registers all event listeners and makes an initial configuration
     */
    RecordingState.prototype.initialize = function() {
        // no binding this object due to keeping reference to "clicked" button
        
        $(this.el.stateBtn).on('click', function (ev) {
            var target = $(ev.target);
            ev.preventDefault();
            if (target.attr('disabled') === "disabled") {
                return;
            }
            this.perform(
                target.attr(this.attributes.action),
                target.attr('href')
            );
        }.bind(this));
    };

    /**
     * Set the
     */
    RecordingState.prototype.updateButton = function(text, action) {
        var btn = $(this.el.stateBtn);
        btn.attr(this.attributes.action, action);
        btn.html(text);
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
                this.call(url, 'PUT', function(){
                    this.updateButton(this.resumeText, this.resumeAction);
                }.bind(this));
                break;
            case this.resumeAction:
                this.call(url, 'PUT', function(){
                    this.updateButton(this.pauseText, this.pauseAction);
                }.bind(this));
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
     * @param {Function} successCallback
     */
    RecordingState.prototype.call = function(url, httpMethod, successCallback) {
        var btn = $(this.el.stateBtn);
        btn.attr('disabled', 'disabled');
        $.ajax({
            url: url,
            type: httpMethod,
            success: function(response) {
                if (response.meta && response.meta.redirect_uri) {
                    window.location.href = response.meta.redirect_uri;
                } else {
                    console.warn('missing redirect_uri metadata in response.');
                    if (typeof successCallback == 'function') {
                        successCallback();
                    }
                    btn.removeAttr('disabled');
                }
            },
            error: function(jqXHR) {
                btn.attr('disabled', false);
                responseFormatter.format(jqXHR);
            }.bind(this)
        });        
    };

    return new RecordingState();
});
