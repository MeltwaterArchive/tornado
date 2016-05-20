define(['jquery', 'underscore', 'mustache', 'modallica', 'draggaball', 'tooltip', 'models/EventModel'],
function($, _, Mustache, Modallica, Draggaball, Tooltip, EventModel) {
    /**
     * Handles the dimensions drag & drop to dropzones
     */

    'use strict';

    /**
     * DimensionsDraggaball constructor
     */
    var DimensionsDraggaball = function(options) {
        var _this = this;

        options = $.isPlainObject(options) ? options : {};
        this.options = _.defaults(options, {
            disabled: {
                x: false,
                y: false,
                z: false
            }
        });

        this.templates = {
            dimension: '[data-tornado-template="dimensions-item"]',
            optionsModal: 'dimensions-options-modal'
        };

        this.el = {
            dimensionsView: '[data-tornado-view="page-content"]',
            sidebarView: '[data-tornado-view="page-sidebar"]',
            dropzone: '.dimension-dropzone',
            dimension: '.dimension',
            dimensionOptions: '.dimension__options',
            dimensionRemove: '.dimension__remove',
            buildButton: '.dimension-build-button',
            threshold: '#dimension-threshold',
            optionsApplyButton: '[data-dimensions-options-apply]'
        };

        this.classes = {
            dimension: 'dimension',
            dropzoneDimension: 'dimension dimension--dropzone',
            dropzoneDimensionActive: 'dimension dimension--dropzone--active',
            dropzoneDimensionDisabled: 'dimension--dropzone--disabled',
            dimensionSelected: 'dimension--selected'
        };

        this.attributes = {
            dropzone: 'data-dimension-dropzone',
            dimension: 'data-dimension-item',
            axis: 'data-dimension-axis',
            target: 'data-dimension-target',
            optionsInfo: 'data-dimension-options-info',
            draggaballItem: 'data-draggaball-item',
            tooltip: 'data-tooltip'
        };

        this.events = {
            mouseup: 'mouseup.draggaball',
            mousedown: 'mousedown.draggaball',
            dropped: 'dropped.draggaball',
            click: 'click.draggaball'
        };

        this.value = {
            x: null,
            y: null,
            z: null
        };

        // If any of the axes/dropzones should be disabled then simply don't make them droppables
        $(this.el.dropzone).each(function() {
            var $dropzone = $(this);
            var axis = $dropzone.attr(_this.attributes.axis);

            if (_this.options.disabled[axis]) {
                $dropzone.removeAttr('data-draggaball-dropzone');
                $dropzone.addClass(_this.classes.dropzoneDimensionDisabled);
            }
        });

        this.draggaball = new Draggaball({
            $draggaballCloneWrapper: $('.dimension-clones-wrapper')
        });

        this.bindEvents();
    };

    /**
     * Listening to dimensions add/remove to/from dropzones
     */
    DimensionsDraggaball.prototype.bindEvents = function() {
        var _this = this;

        // Unbind already registered events
        this.unbindEvents();

        $('body').on(this.events.dropped, function(e, data) {
            _this.onDropped(data.$dropzone, data.$draggaball);
        });

        $(this.el.dimensionsView).on(this.events.click, this.el.dimensionRemove, function(ev) {
            ev.preventDefault();

            var $dimension = $(this).closest(_this.el.dimension);
            _this.onRemove($dimension);
        });

        $(this.el.dimensionsView).on(this.events.click, this.el.dimensionOptions, function(ev) {
            ev.preventDefault();

            var $dimension = $(this).closest(_this.el.dimension);
            _this.dimensionOptions($dimension);
        });
    };

    DimensionsDraggaball.prototype.unbindEvents = function() {
        $('body').off(this.events.dropped);
        $('body').off(this.events.mouseup);
        $(this.el.dimensionsView).off(this.events.click);
        $(this.el.dimensionsView).off(this.events.mousedown);
    };

    /**
     * Gets the current value / set dimensions
     *
     * @return {Object}
     */
    DimensionsDraggaball.prototype.getValue = function() {
        return this.value;
    };

    /**
     * Sets the value of the dimensions
     *
     * @param {Object} value Object with x, y, z properties.
     */
    DimensionsDraggaball.prototype.setValue = function(value) {
        var _this = this;

        _.each(['x', 'y', 'z'], function(axis) {
            var dimension = value[axis] || null;

            // if this axis is disabled then set null target after all
            if (_this.options.disabled[axis]) {
                dimension = null;
            }

            var $dropzone = $(_this.el.dimensionsView)
                .find(_this.el.dropzone)
                    .filter('[' + _this.attributes.axis + '="' + axis + '"]');

            if (!dimension) {
                _this.value[axis] = null;

                $dropzone
                    .empty()
                    .attr(_this.attributes.dropzone, '');

                return;
            }

            _this.addDimensionToDropzone($dropzone, dimension);
        }.bind(this));

        this.highlightActiveDraggaballs();
        this.changeBuildButtonState();
    };

    /**
     * Method called after dropping a dimension in a dropzone
     *
     * @param  {jQuery} $dropzone   Dropzone jQuery object
     * @param  {jQuery} $dimension  Dimension jQuery object
     */
    DimensionsDraggaball.prototype.onDropped = function($dropzone, $dimension) {
        var dimension = {
            target: $dimension.attr(this.attributes.target),
            label: $dimension.attr(this.attributes.dimension),
            description: $dimension.attr(this.attributes.tooltip),
            cardinality: $dimension.attr('data-cardinality'),
            threshold: 100
        };

        EventModel.trigger('dimensionDrop', dimension);

        this
            .addDimensionToDropzone($dropzone, dimension)
            .highlightActiveDraggaballs()
            .changeBuildButtonState();
    };

    /**
     * Method called after removing a dimension from a dropzone
     * @param  {jQeury} $dimension Dimension jQuery object
     */
    DimensionsDraggaball.prototype.onRemove = function($dimension) {
        var $dropzone = $dimension.closest(this.el.dropzone),
            dimension = {
                target: $dimension.attr(this.attributes.target),
                name: $dimension.attr(this.attributes.dimension),
                tooltip: $dimension.attr(this.attributes.tooltip),
                cardinality: $dimension.attr('data-cardinality'),
                threshold: 100
            };

        var axis = $dimension.attr(this.attributes.axis);
        this.value[axis] = null;

        $dimension.remove();
        $dropzone.attr(this.attributes.dropzone, '');

        EventModel.trigger('dimensionRemove', dimension);

        this
            .highlightActiveDraggaballs()
            .changeBuildButtonState();
    };

    /**
     * Displays and manages a dimension options modal.
     *
     * @param  {jQuery} $dimension Dimension.
     */
    DimensionsDraggaball.prototype.dimensionOptions = function($dimension) {
        var _this = this;

        var axis = $dimension.attr(this.attributes.axis);
        var dimension = this.value[axis];

        // When Modallica shows up, listen for clicks on the confirmation button
        $(document).one('dimensions-options-modal:ready.modallica', function() {
            $(_this.el.optionsApplyButton).click(function(ev) {
                ev.preventDefault();

                dimension.threshold = parseInt($(_this.el.threshold).val(), 10);
                if (!Number.isNaN(dimension.threshold)) {
                    $dimension.attr(_this.attributes.optionsInfo, 'Threshold: ' + dimension.threshold);
                } else {
                    $dimension.attr(_this.attributes.optionsInfo, '');
                }

                Modallica.hide();
            });
        });

        Modallica.showFromData({
            title: dimension.name + ' Options',
            templateName: 'dimensions-options-modal',
            dimension: dimension
        });
    };

    /**
     * Construct a new dimension element, after dropping a dimension to a dropzone
     *
     * @param {jQuery} $dropzone   Dropzone jQuery object
     * @param {Object} dimension   Dimension data object.
     */
    DimensionsDraggaball.prototype.addDimensionToDropzone = function($dropzone, dimension) {
        var axis = $dropzone.attr(this.attributes.axis),
            template = $(this.templates.dimension).html();

        var $dropzoneDimension = $(Mustache.render(template, {
            axis: axis,
            dimension: dimension
        }));

        $dropzone
            .html('')
            .append($dropzoneDimension)
            .attr(this.attributes.dropzone, 'complete');

        // Introducing a setTimeout, to allow some browser ticks after the append happens.
        // We're doing this to allow the transition to work.
        setTimeout(function() {
            $dropzoneDimension.addClass(this.classes.dropzoneDimensionActive);
        }.bind(this), 10);

        // update the dimensions value
        this.value[axis] = dimension;

        return this;
    };

    /**
     * Method called after adding or removing dimensions to/from a dropzone.
     * Highlights or removes the highlight depending on if the dropzones contain
     * dimensions or not.
     *
     * @return {Object} DimensionsDraggaball instance
     */
    DimensionsDraggaball.prototype.highlightActiveDraggaballs = function() {
        var $selectedDimensions = $(this.el.dimensionsView).find(this.el.dimension);

        $(this.el.sidebarView)
            .find(this.el.dimension)
                .removeClass(this.classes.dimensionSelected);

        _.each($selectedDimensions, function(dimension, index) {
            var dimensionTarget = $(dimension).attr(this.attributes.target);

            $(this.el.sidebarView)
                .find('[' + this.attributes.target + '="' + dimensionTarget + '"]')
                    .addClass(this.classes.dimensionSelected);
        }.bind(this));

        return this;
    };

    /**
     * Method called after adding or removing dimensions to/from a dropzone.
     * Enables or disables the button depending on if the relevant dropzones
     * (or axis - if you will -) contain dimensions.
     *
     * @return {Object} DimensionsDraggaball instance
     */
    DimensionsDraggaball.prototype.changeBuildButtonState = function() {
        var _this = this;
        var $buildButton = $(this.el.buildButton);
        var $dropzones = $(this.el.dimensionsView).find(this.el.dropzone);
        var dropzoneAxes = {};

        _.each($dropzones, function(dropzone) {
            var $dropzone = $(dropzone);
            var $dropzoneDimension = $dropzone.find(this.el.dimension);
            var axis = $dropzone.data('dimension-axis');

            dropzoneAxes[axis] = ($dropzoneDimension.length > 0 || _this.options.disabled[axis]);
        }.bind(this));

        var allowBuild = ('x' in dropzoneAxes && 'y' in dropzoneAxes && dropzoneAxes.x && dropzoneAxes.y);

        if (allowBuild) {
            $buildButton.prop('disabled', '');
        } else {
            $buildButton.prop('disabled', 'disabled');
        }

        return this;
    };

    return DimensionsDraggaball;
});
