/**
 * Checkmate initialization
 */
require(['jquery', 'checkmate'], function($, Checkmate) {
    'use strict';

    var CheckmateInit = function() {
        this.el = {
            masterCheckbox: '[data-checkmate]',
            listItem: '.list-item'
        };

        this.classes = {
            selectedItem: 'list-item--selected'
        };

        this.attributes = {
            masterCheckbox: 'data-checkmate'
        };

        this.initialize();
    };

    CheckmateInit.prototype.initialize = function() {
        $(this.el.masterCheckbox).each(function(index, item) {
            var masterCheckboxName = $(item).attr(this.attributes.masterCheckbox);

            new Checkmate({
                name: masterCheckboxName,
                onUpdate: function() {
                    this.highlightRows($('[data-checkmate-child="' + masterCheckboxName + '"]'));
                }.bind(this)
            });
        }.bind(this));
    };

    CheckmateInit.prototype.highlightRows = function($checkboxes) {
        _.each($checkboxes, function(checkbox) {
            var $checkbox = $(checkbox);

            if ($checkbox.prop('checked')) {
                $checkbox
                    .closest(this.el.listItem)
                        .addClass(this.classes.selectedItem);
            } else {
                $checkbox
                    .closest(this.el.listItem)
                        .removeClass(this.classes.selectedItem);
            }
        }.bind(this));
    };

    return new CheckmateInit();
});
