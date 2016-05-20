define(['jquery', 'underscore'],
function($, _) {
    'use strict';

    /**
     * Checkmate
     * ##########
     *
     * ## Description
     * Control your checkboxes using a 'Master checkbox'
     *
     * ## Usage
     * 1. Add `data-checkmate="{{ name }}"` attribute to the checkbox element that will control the 'children'
     * 2. Add `data-checkmate-child="{{ name }}"` attribute to the 'children' checkboxes
     * 3. Initialize: `new checkmate(options);`
     */
    var Checkmate = function(options) {
        this.options = $.extend({
            name: '',
            classes: {
                semi: 'styled-checkbox--semi'
            },
            onUpdate: {}
        }, options);

        this.el = {
            master: '[data-checkmate="' + this.options.name + '"]',
            child: '[data-checkmate-child="' + this.options.name + '"]'
        };

        this.attributes = {
            master: 'data-checkmate',
            child: 'data-checkmate-child'
        };

        this.$body = $('body');

        this.initialize();
    };

    Checkmate.prototype = {
        totalCheckboxes: 0,
    };

    Checkmate.prototype.initialize = function() {
        this
            .updateTotalCheckboxes()
            .bindEvents();
    };

    Checkmate.prototype.bindEvents = function() {
        var _this = this;

        // Event when clicking the master checkbox
        this.$body.on('click', this.el.master, function(ev) {
            _this.masterUpdate($(this).prop('checked'));

            if (_.isFunction(_this.options.onUpdate)) {
                _this.options.onUpdate();
            };
        });

        // Event when clicking a child checkbox
        this.$body.on('click', this.el.child, function(ev) {
            this.update();

            if (_.isFunction(this.options.onUpdate)) {
                this.options.onUpdate();
            };
        }.bind(this));
    };

    Checkmate.prototype.updateTotalCheckboxes = function() {
        this.totalCheckboxes = $(this.el.child).length;

        return this;
    };

    /**
     * Check all checkboxes
     *
     * @return {Object} Checkmate instance
     */
    Checkmate.prototype.checkAll = function() {
        _.each($(this.el.child), function(child) {
            $(child).prop('checked', true);
        });

        return this;
    };

    /**
     * Unheck all checkboxes
     *
     * @return {Object} Checkmate instance
     */
    Checkmate.prototype.uncheckAll = function() {
        _.each($(this.el.child), function(child) {
            $(child).prop('checked', false);
        });

        return this;
    };

    /**
     * Method called after clicking on the master checkbox.
     * Checks or unchecks all children checkboxes depending
     * on the master's prop.
     * @param  {Boolean} checked Master checkbox's checked property
     * @return {Object}          Checkmate instance
     */
    Checkmate.prototype.masterUpdate = function(checked) {
        if (checked) {
            this.checkAll();
        } else {
            this.uncheckAll();
        }

        $(this.el.master).removeClass(this.options.classes.semi);

        return this;
    };

    /**
     * Method called after updating the checked property
     * of a child checkbox. Checks the master checkbox
     * and updates the necessary classes.
     *
     * @return {Object} Checkmate instance
     */
    Checkmate.prototype.checkMaster = function() {
        $(this.el.master)
            .prop('checked', true)
            .removeClass(this.options.classes.semi);

        return this;
    };

    /**
     * Method called after updating the checked property
     * of a child checkbox. Unchecks the master checkbox
     * and updates the necessary classes.
     *
     * @return {Object} Checkmate instance
     */
    Checkmate.prototype.uncheckMaster = function() {
        $(this.el.master)
            .prop('checked', false)
            .removeClass(this.options.classes.semi);

        return this;
    };

    /**
     * Method called after updating the checked property
     * of a child checkbox. Semi-checks the master checkbox
     * and updates the necessary classes.
     *
     * @return {Object} Checkmate instance
     */
    Checkmate.prototype.semiCheckMaster = function() {
        $(this.el.master)
            .prop('checked', false)
            .addClass(this.options.classes.semi);

        return this;
    };

    /**
     * Update the master checkbox whenever there's
     * a change on any of the child checkboxes.
     *
     * @return {Object} Checkmate instance
     */
    Checkmate.prototype.update = function() {
        var checkedCheckboxes = 0;
        var result;

        _.each($(this.el.child), function(child) {
            if ($(child).prop('checked')) {
                checkedCheckboxes++;
            }
        });

        result = this.totalCheckboxes - checkedCheckboxes;

        // Case: All checked
        if (result === 0) {
            this.checkMaster();
        // Case: All unchecked
        } else if (result === this.totalCheckboxes) {
            this.uncheckMaster();
        // Case: Some checked
        } else {
            this.semiCheckMaster();
        }

        return this;
    };

    return Checkmate;
});
