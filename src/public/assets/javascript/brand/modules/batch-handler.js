/**
 * Batch delete Module
 *
 * @param {jQuery}
 * @param {Underscore}
 * @param {ErrorFormatter}
 */
require(['jquery', 'underscore', 'buzzkill', 'responseFormatter', 'modallica'],
    function($, _, Buzzkill, errorFormatter, Modallica) {
        'use strict';

        /**
         * Batch action class that performs batch action calls to the API
         *
         * @constructor
         */
        var BatchHandler = function() {
            this.el = {
                form: '[data-batch-form]',
                listItem: '[data-checkmate-child]',
                deleteSubmit: '[data-batch-delete]',
                deleteConfirm: '[data-confirm-delete]'
            };
            this.submitUrl = $(this.el.form).attr('action');

            this.initialize();
        };

        /**
         * Registers all event listeners and makes an initial configuration
         */
        BatchHandler.prototype.initialize = function() {
            $(this.el.deleteSubmit).on('click', function(ev) {
                ev.preventDefault();

                this.delete();
            }.bind(this));
        };

        /**
         * Counts the checked list items
         *
         * @returns {Number}
         */
        BatchHandler.prototype.countChecked = function() {
            var checked = 0;

            _.each($(this.el.listItem), function(item) {
                if ($(item).prop('checked')) {
                    checked++;
                }
            }.bind(this));

            return checked;
        };

        /**
         * Performs batch delete action
         */
        BatchHandler.prototype.delete = function() {
            var _this = this;
            var count = this.countChecked();

            if (!count > 0) {
                Buzzkill.notice('Check at least one item to perform an action.');
                return;
            }

            // When Modallica shows up, listen for clicks on the confirmation button
            $(document).on('confirm-delete-batch:ready.modallica', function() {
                $(_this.el.deleteConfirm).click(function(ev) {
                    ev.preventDefault();

                    var data = $(_this.el.form).serialize();
                    _this.submit(data + '&action=delete');
                });
            });

            Modallica.showFromData({
                title: 'Are you sure?',
                templateName: 'confirm-delete-batch',
                count: count
            });
        };

        /**
         * Performs a batch action submit and handles a server response
         *
         * @param {*} data
         */
        BatchHandler.prototype.submit = function(data) {
            $.ajax({
                url: this.submitUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.meta && response.meta.redirect_uri) {
                        window.location.href = response.meta.redirect_uri;
                    } else {
                        console.warn('missing redirect_uri metadata in response.');
                    }
                },
                error: function(jqXHR) {
                    errorFormatter.format(jqXHR);
                }.bind(this)
            });
        };

        return new BatchHandler();
    });
