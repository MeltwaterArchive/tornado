define(['jquery', 'underscore'], function($, _) {
    /**
     * Draggaball
     * ##########
     *
     * ## Description
     * Drag & drop elements (the "draggaballs") to other elements (the "dropzones").
     *
     * ## Usage
     * 1. Add `data-draggaball-item` attribute to the elements you want to be able to drag
     * 2. Add `data-draggaball-dropzone` attribute to the elements you want to use as dropzones
     * 3. Initialize: `new Draggaball(options);`
     *
     */

    'use strict';

    /**
     * Helper function that returns the "clean" name of a Class, Id, or Data attribute
     *
     * @param  {String} name The name of the class/id/data attribute
     * @return {String}      Clean name
     */
    var getRawName = function(name) {
        return name.replace(/[.#\[\]]/g, '');
    };

    /**
     * Helper method to return prefixed translate() object
     *
     * @param  {Number} x Left offset
     * @param  {Number} y Top offset
     * @return {Object}   Object containing the final rules for `.css()`
     */
    var doTranslate = function(x, y) {
        var result = {};
        var prefix = ['WebkitTransform', 'transform'];

        prefix.forEach(function(key, index) {
            result[prefix[index]] = 'translate(' + x + 'px, ' + y + 'px)';
        });

        return result;
    }

    /**
     * Draggaball constructor
     */
    var Draggaball = function(options) {
        this.defaults = {
            // Assign this class to the body element, when dragging is initiated
            draggingBodyClass: 'draggaball-body-dragging',

            // Clone the target draggaball and move that instead
            clone: true,

            // Element to append the cloned draggaball to
            $draggaballCloneWrapper: null
        };

        // Draggaball element
        this.el = '[data-draggaball-item]';

        // Dropzone element
        this.dropzoneEl = '[data-draggaball-dropzone]';

        // Value to be assigned to the dropzone's data attribute, when hovered
        this.dropzoneDroppableValue = 'droppable';

        // Value to be assigned to the draggaball's data attribute, when dragging is initiated
        this.draggingDraggaballValue = 'dragging';

        // Value to be assigned to the cloned draggaball's data attribute, if there is one
        this.draggaballCloneValue = 'clone';

        // Cache the body element
        this.$body = $('body');

        // Object with namespaced event names
        this.events = {
            mousedown: 'mousedown.draggaball',
            mousemove: 'mousemove.draggaball',
            mouseup: 'mouseup.draggaball',
            dragging: 'dragging.draggaball',
            dropped: 'dropped.draggaball',
            cancel: 'cancel.draggaball'
        };

        // Merge the custom options in
        this.options = $.extend(true, {}, this.defaults, options || {});

        this.bindEvents();
    };

    Draggaball.prototype = {
        // Storing the dropzone elements and their positions in the DOM
        dropzones: [],

        // The element being dragged
        $draggaball: null,

        // Storing the pageX and pageY on `mousedown` to keep it as a
        // reference for our calculations
        onMousedownXY: null,

        onMousedownScroll: null
    };

    /**
     * Register all Draggable events
     */
    Draggaball.prototype.bindEvents = function() {
        var _this = this;

        this.$body.on(this.events.mousedown, this.el, function(e) {
            e.preventDefault();

            _this
                .prepareDraggaball($(this), e)
                .onMouseDown(e);
        });

        this.$body.on(this.events.dragging, function(e, data) {
            _this.onDrag(data);
        });
    };

    Draggaball.prototype.unload = function() {
        this.$body.off('.draggaball');
    };

    /**
     * Clear all stored data when we're not dragging
     * and clean up any custom attributes/classes we
     * may have set
     */
    Draggaball.prototype.cleanUp = function() {
        // Take care of the body
        this.$body.removeClass(this.options.draggingBodyClass);

        // Take care of all the draggaball-related elements
        $(this.dropzoneEl).attr(getRawName(this.dropzoneEl), '');
        $(this.el).attr(getRawName(this.el), '');

        // If there is a cloned element remove it
        if (this.options.clone) {
            this.$draggaball.remove();

        // Otherwise clear the inline styles
        // of the original draggaball
        } else {
            $(this.el).removeAttr('style');
        }

        // Reset all the variables
        this.dropzones = [];
        this.$draggaball = null;
        this.onMousedownXY = null;
        this.$targetDropzone = null;
    };

    /**
     * Prepare/set the draggaball element, handle cloning, store initial values etc
     *
     * @param  {jQuery} $draggaball Draggaball element that was clicked
     * @param  {Object} e           Mousedown event object
     * @return {Object}             Draggaball instance
     */
    Draggaball.prototype.prepareDraggaball = function($draggaball, e) {
        // Storing the initial mouse coords
        this.onMousedownXY = {
            top: e.pageY,
            left: e.pageX
        }

        this.onMousedownScroll = $(window).scrollTop();

        // Storing the draggaball
        this.$draggaball = (this.options.clone)
            ? this.cloneDraggaball($draggaball)
            : $draggaball;

        $draggaball.attr(getRawName(this.el), this.draggingDraggaballValue);

        return this;
    };

    /**
     * Clones the draggaball. Simple as that.
     *
     * @param  {jQuery} $draggaball Draggaball jQuery object
     * @return {jQuery}             Cloned Draggaball jQuery object
     */
    Draggaball.prototype.cloneDraggaball = function($draggaball) {
        var _this = this;
        var $clonedDraggaball = $draggaball.clone();
        var bBox = $draggaball[0].getBoundingClientRect();

        $clonedDraggaball
            .attr(getRawName(this.el), this.draggaballCloneValue)
            .css({
                position: 'absolute',
                top: 0,
                left: 0
            });

        // Deciding where to drop the cloned draggaball
        if (_.isNull(this.options.$draggaballCloneWrapper)) {
            $clonedDraggaball.insertAfter($draggaball);
        } else {
            this.options.$draggaballCloneWrapper.css({
                position: 'fixed',
                top: bBox.top + (_this.onMousedownScroll * 2),
                left: bBox.left,
                margin: 0,
                padding: 0,
                zIndex: 101
            });

            $clonedDraggaball.appendTo(this.options.$draggaballCloneWrapper);
        }

        return $clonedDraggaball;
    };

    /**
     * Method called when we `mousedown` on a draggaball
     *
     * @param  {Object} e Mousedown event object
     */
    Draggaball.prototype.onMouseDown = function(e) {
        // Don't allow right button clicks
        if (e.which === 3 || e.button === 2) {
            return false;
        }

        this.cacheDropzonesPosition();

        this.$body.on(this.events.mousemove, function(ev) {
            this.onMousemove(ev);
        }.bind(this));

        this.$body.one(this.events.mouseup, function() {
            this.onMouseup();
            this.cleanUp();
            this.$body.off(this.events.mousemove);
        }.bind(this));
    };

    /**
     * Method called when we `mousemove` a draggaball
     *
     * @param  {Object} e Mousemove event object
     */
    Draggaball.prototype.onMousemove = function(e) {
        if (!this.$body.hasClass(this.options.draggingBodyClass)) {
            this.$body.addClass(this.options.draggingBodyClass);
        }

        this.$body.trigger(this.events.dragging, [{ event: e, $draggaball: this.$draggaball }]);
    };

    /**
     * Method called when we `mouseup` after dragging a draggaball.
     * Also, it triggers the correct event and passes on the draggaball
     * and dropzone elements as custom data.
     */
    Draggaball.prototype.onMouseup = function() {
        var $activeDropzone = $('[' + getRawName(this.dropzoneEl) + '="' + this.dropzoneDroppableValue + '"]');
        var $activeDraggaball = $('[' + getRawName(this.el) + '="' + this.draggingDraggaballValue + '"]');

        // `dropped` event for successful drop.
        // `cancel` event otherwise.
        var finalEvent = ($activeDropzone.length > 0)
            ? this.events.dropped
            : this.events.cancel;

        this.$body.trigger(finalEvent, [{
            $draggaball: $activeDraggaball,
            $dropzone: $activeDropzone
        }]);
    };

    /**
     * Stores the dropzone elements and their coordinates/size
     */
    Draggaball.prototype.cacheDropzonesPosition = function() {
        _.each($(this.dropzoneEl), function(item) {
            var boundingClientRect = item.getBoundingClientRect();
            var finalBBox = {
                top: boundingClientRect.top + $(window).scrollTop(),
                left: boundingClientRect.left,
                width: boundingClientRect.width,
                height: boundingClientRect.height
            };

            var data = {
                $el: $(item),
                bBox: finalBBox
            };

            this.dropzones.push(data);
        }.bind(this));
    };

    /**
     * Called when dragging a draggaball around
     *
     * @param  {Object} data Custom event data
     */
    Draggaball.prototype.onDrag = function(data) {
        var x = data.event.pageX - this.onMousedownXY.left;
        var y = data.event.pageY - this.onMousedownXY.top - this.onMousedownScroll - $(window).scrollTop();

        this
            .dragItem(x, y)
            .checkIfDroppable(data.event.pageX, data.event.pageY);
    };

    /**
     * Applies the css rules on the draggaball to move it around
     *
     * @param  {Number} x Left position
     * @param  {Number} y Top position
     * @return {Object}   Draggaball instance
     */
    Draggaball.prototype.dragItem = function(x, y) {
        this.$draggaball.css(doTranslate(x, y));

        return this;
    };

    /**
     * Checks if the given coordinates colide with the object
     *
     * @param  {Number}  x      Left position
     * @param  {Number}  y      Top position
     * @param  {Object}  object The element's BoundingClientRect
     * @return {Boolean}        Do they collide?
     */
    Draggaball.prototype.isColliding = function(x, y, object) {
        return (x < object.left + object.width) && x >= object.left &&
            (y < object.top + object.height) && y >= object.top;
    }

    /**
     * Iterates through the dropzones and checks if the
     * draggaball collides with any of them. Then sets
     * the appropriate values.
     *
     * @param  {Number} x Left position
     * @param  {Number} y Top position
     */
    Draggaball.prototype.checkIfDroppable = function(x, y) {
        _.each(this.dropzones, function(dropzone) {
            if (this.isColliding(x, y, dropzone.bBox)) {
                var rawDropzoneName = getRawName(this.dropzoneEl);

                if (dropzone.$el.attr(rawDropzoneName) !== this.dropzoneDroppableValue) {
                    dropzone.$el.attr(rawDropzoneName, this.dropzoneDroppableValue);
                }
            } else {
                dropzone.$el.attr(getRawName(this.dropzoneEl), '');
            }
        }.bind(this));
    };

    return Draggaball;
});
