define([
    'jquery', 
    'underscore', 
    'buzzkill'
], function($, _, Buzzkill) {
    
    'use strict';

    /**
     * Http error formatter service responsible for formatting and displaying errors or doing custom
     * callback actions.
     *
     * @singleton
     */
    var ErrorFormatter = function () {
        // keep an array of errors
        this.errors = [];
        // listen for a successful ajax request
        $(document).ajaxSuccess(this._success.bind(this));
    };

    /**
     * When we have a successful AJAX event we want to clear all the messages
     *
     * @todo This should not be a global, ideally all the AJAX request should
     * be abstracted to a AJAX handler which will do this. This will also 
     * abstract out jQuery making it easier to replace!
     */
    ErrorFormatter.prototype._success = function (evt, xhr, settings) {

        var index = this.errors.indexOf(settings.url);

        if (index !== -1) {
            // clear any error messages
            this.clear();
            // remove from the array
            this.errors.splice(index, 1);
        }
    };

    /**
     * Formats Server Errors
     *
     * Show Buzzkill error messages when we get an invalid response from the
     * server.
     *
     * This will also add the URL of the request into the error array if we
     * want the error to be removed at a later date.
     *
     * Currently only the analyze call implements the URL parameter
     * 
     * @param  {Object}   jqXHR    JQuery XHR objext
     * @param  {Function} callback Callback to override the default behaviour
     * @param  {String}   url      URL, if we want the error to be removed
     */
    ErrorFormatter.prototype.format = function(jqXHR, callback, url) {
        
        if (url) {
            // add to the error array
            this.errors.push(url);
        }

        if (_.isFunction(callback)) {
            // shortcut to override the default action
            return callback(jqXHR);
        }

        try {
            var response = JSON.parse(jqXHR.responseText);

            if (response.meta) {
                if (response.meta.error) {
                    Buzzkill.notice(response.meta.error);
                }

                if (response.meta.redirect_uri) {
                    window.location.href = response.meta.redirect_uri;
                }
            }
        } catch (e) {
            Buzzkill.notice(jqXHR.statusText);
        }
    };

    /**
     * Clears error alert if any exists.
     */
    ErrorFormatter.prototype.clear = function() {
        Buzzkill.clearNotice();
    };

    return new ErrorFormatter();
});
