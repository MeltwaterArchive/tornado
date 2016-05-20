/**
 * Delete listener for handling a single delete actions
 *
 * @param {jQuery}
 * @param {ErrorFormatter}
 */
require(['jquery', 'responseFormatter', 'modallica'],
function($, responseFormatter, Modallica) {
    'use strict';

    /**
     * Delete handler class that performs delete calls to the API
     *
     * @constructor
     */
    var DeleteHandler = function() {
        this.el = {
            deleteBtn: '[data-delete]',
            confirmDeleteBtn: '[data-confirm-delete]'
        };

        this.attributes = {
            itemName: 'data-delete'
        };

        this.initialize();
    };

    /**
     * Registers all event listeners and makes an initial configuration
     */
    DeleteHandler.prototype.initialize = function() {
        var _this = this;

        // no binding this object due to keeping reference to "clicked" button
        $(this.el.deleteBtn).on('click', function(ev) {
            ev.preventDefault();

            var $this = $(this);
            var url = $this.attr('href');
            var itemName = $this.attr(_this.attributes.itemName);

            _this.confirm(itemName, url);
        });
    };

    /**
     * Confirm to delete.
     *
     * @param  {String} itemName Name of the item that is about to be deleted.
     * @param  {String} url      URL to send DELETE request to.
     */
    DeleteHandler.prototype.confirm = function(itemName, url) {
        var _this = this;

        // When Modallica shows up, listen for clicks on the confirmation button
        $(document).on('confirm-delete:ready.modallica', function() {
            $(_this.el.confirmDeleteBtn).click(function(ev) {
                ev.preventDefault();

                _this.delete(url);
            });
        });

        Modallica.showFromData({
            title: 'Are you sure?',
            templateName: 'confirm-delete',
            item: (itemName && itemName.length) ? itemName : 'this'
        });
    };

    /**
     * Performs a delete action and handles a server response
     *
     * @param {String} url
     */
    DeleteHandler.prototype.delete = function(url) {
        $.ajax({
            url: url,
            type: 'DELETE',
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

    return new DeleteHandler();
});
