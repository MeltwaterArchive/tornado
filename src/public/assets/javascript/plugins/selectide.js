define(['jquery', 'mustache', 'selectize', 'underscore'],
function($, Mustache, selectize, _) {
    'use strict';

    /**
     * Wrapper around the Selectize plugin that customizes its display to show
     * selected items outside of the input field.
     *
     * @param {String|DOMElement|jQuery} el      Element (or selector) to initialize on.
     * @param {Object}                   options Options (passed through to Selectize).
     */
    var Selectide = function(el, options) {
        options = $.isPlainObject(options) ? options : {};

        this.$el = $(el);

        // delay setting values until everything is fully initialized
        var value = options.items || [];
        options.items = [];

        this.initSelectize(options);
        this.render();
        this.bindEvents();

        this.setValue(value);
    };

    /**
     * Template for list of selected items.
     *
     * @type {String}
     */
    Selectide.prototype.selectionTemplate = $('[data-tornado-template="selectide-selection"]').html();

    /**
     * Template for a single selected item.
     *
     * @type {String}
     */
    Selectide.prototype.itemTemplate = $('[data-tornado-template="selectide-selection__item"]').html();

    /**
     * Initialize selectize plugin.
     *
     * @param {Object} options Selectize options.
     *
     * @return {Selectize}
     */
    Selectide.prototype.initSelectize = function(options) {
        /**
         * Prevent deleting items from inside the selectize input (using BACKSPACE).
         *
         * @param  {Array} items Items to be deleted.
         *
         * @return {Boolean}
         */
        options.onDelete = function(items) {
            var allow = true;

            _.each(items, function(item) {
                if (this.isSelected(item)) {
                    allow = false;
                }
            }.bind(this));

            return allow;
        }.bind(this);

        this.$el.selectize(options);

        // get reference to selectize and its elements
        this.selectize = this.$el[0].selectize;
        this.$selectizeEl = this.$el.next('.selectize-control');

        return this.selectize;
    };

    /**
     * Render Selectide / customize the view.
     */
    Selectide.prototype.render = function() {
        this.$selectizeEl.addClass('selectided');
        this.$selection = $(this.selectionTemplate).insertAfter(this.$selectizeEl);
    };

    /**
     * Bind various events.
     */
    Selectide.prototype.bindEvents = function() {
        /**
         * When an item was added to selectize, then hijack it and add it to our list.
         *
         * @param  {String} value           Item value.
         * @param  {jQuery} $selectizeItem  Item element from selectize.
         */
        this.selectize.on('item_add', function(value, $selectizeItem) {
            var template = Mustache.render(this.itemTemplate, {
                value: value,
                text: $selectizeItem.text()
            });

            $(template).appendTo(this.$selection);
        }.bind(this));

        /**
         * When an item was removed from selectize, then also remove from our list.
         *
         * @param  {String} value Removed item value.
         */
        this.selectize.on('item_remove', function(value) {
            this.$selection.find('[data-value="' + value + '"]').remove();
        }.bind(this));

        /**
         * When clicked on a selected item's remove element - remove it.
         *
         * @param  {$.Event} ev Click event.
         */
        this.$selection.on('click', '.selectide-selection__item-remove', function(ev) {
            ev.preventDefault();

            var $item = $(ev.target).closest('.selectide-selection__item');
            var item = $item.data('value');
            $item.remove();

            this.selectize.removeItem(item);
        }.bind(this));
    };

    /**
     * Add options.
     *
     * @param {Array} options Array of options (objects with text and value keys).
     */
    Selectide.prototype.add = function(options) {
        _.each(options, function(option) {
            this.selectize.addOption(option);
        }.bind(this));
        this.selectize.refreshOptions(false);
    };

    /**
     * Select an item.
     *
     * @param  {String} item Item value to be selected.
     */
    Selectide.prototype.select = function(item) {
        this.selectize.addItem(item);
        this.selectize.refreshItems();
    };

    /**
     * Checks if the given item is currently selected.
     *
     * @param  {String}  item Item value.
     *
     * @return {Boolean}
     */
    Selectide.prototype.isSelected = function(item) {
        return this.$selection.find('[data-value="' + item + '"]').length > 0;
    };

    /**
     * Clears the selection.
     */
    Selectide.prototype.clearSelection = function() {
        this.selectize.clear();
        this.$selection.empty();
    };

    /**
     * Gets the current value.
     *
     * @return {String|Array}
     */
    Selectide.prototype.getValue = function() {
        return this.selectize.getValue();
    };

    /**
     * Resets the selected items to the given value.
     *
     * @param {Array} items Array of items to set.
     */
    Selectide.prototype.setValue = function(items) {
        this.selectize.setValue(items);
    };

    return Selectide;
});
