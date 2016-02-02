/**
 * ScrollFoo, a jQuery + BEM-friendly scrollbar plugin v0.1
 *
 * ==============================
 * DOM
 * ==============================
 *  <div class="scrollfoo__content-wrapper">
 *      <!-- Scrollbar element -->
 *      <span class="scrollfoo__scroller scrollfoo__scroller--{{custom-modifier}}"></span>
 *      <!-- Parent element with overflow: hidden -->
 *      <div class="scrollfoo__parent scrollfoo__parent--{{custom-modifier}}"></div>
 *  </div>
 *
 * ==============================
 * Initialization
 * ==============================
 *  window.newScrollFooInstance = new ScrollFoo({
 *      parentEl: '{{parent-element}}',
 *      scrollerEl: '.scrollfoo__scroller--{{custom-modifier}}',
 *      visibleParentHeight: 300,
 *      realParentHeight: function() {
 *          return $('.scrollfoo__parent--{{custom-modifier}}').outerHeight();
 *      }
 *  });
 *
 * @link https://github.com/vslio/scrollFoo
 * @author Kostas Vasileiou <hello@vsl.io>
 * @license http://opensource.org/licenses/MIT MIT
 */
;(function(scrollfoo) {
    'use strict';

    /**
     * ScrollFoo Constructor
     */
    var ScrollFoo = function(options) {
        this.options = options || {};

        this.defaults = {
            // Element where the scrolling is active
            parentEl: '',

            // Scrollbar element
            scrollerEl: '.scrollfoo__scroller',

            // Visible height of parent element
            visibleParentHeight: 0,

            // Real height of parent element
            realParentHeight: 0,

            // Storing the visible content height to
            // real container height ratio
            ratio: 0,

            // Setting ScrollFoo active/inactive
            state: false,

            // Give the parent some room
            visibleParentHeightOffset: 0,

            // Added to the content wrapper element
            // when we're dragging the scrollbar
            draggingClass: 'scrollfoo--dragging',

            disabledClass: 'scrollfoo__scroller--disabled'
        };

        // Caching the body element
        this.$body = $('body');

        // Merging user options with plugin defaults
        this.config = $.extend(true, {}, this.defaults, this.options);

        this.initialize();
    };

    ScrollFoo.prototype.initialize = function() {
        this.registerEvents();
        this.doCalculate();
    };

    ScrollFoo.prototype.registerEvents = function() {
        var _this = this;

        this.unload();

        // ScrollFoo scroll event
        $(this.config.parentEl).on('scroll mousewheel DOMMouseScroll MozMousePixelScroll', function(ev) {
            _this.onScrollEvent($(this), ev);
        });

        // Dragging the scrollbar
        $(this.config.scrollerEl).on('mousedown', function(ev) {
            _this.onMouseDownEvent($(this), ev);
        });
    };

    ScrollFoo.prototype.unload = function() {
        $(this.config.parentEl).off('scroll mousewheel DOMMouseScroll MozMousePixelScroll');
        $(this.config.scrollerEl).off('mousedown');
    };

    /**
     * Method called when scrolling inside a ScrollFoo enabled element
     *
     * @param  {jQuery} $target ScrollFoo element
     * @param  {Object} ev      mousewheel/DOMMouseScroll/MozMousePixelScroll event object
     */
    ScrollFoo.prototype.onScrollEvent = function($target, ev) {
        var scrolledPos;

        // Don't hijack scrolling when we're hovering over expanded selectize dropdowns
        //
        // @todo  Make it prettier... maybe? It's too specific. Or is it...?
        if ($(ev.target).hasClass('option') && ($(ev.target).parents('.selectize-control').length > 0)) {
            return false;
        }

        if (ev.type === 'scroll') {
            scrolledPos = $target.scrollTop();

            if (typeof ev.originalEvent.wheelDeltaY == 'undefined' && this.config.state === true) {
                this.handleReelbarScroller(scrolledPos, false);
            }
        } else {
            if (ev.type !== 'DOMMouseScroll' && ev.type !== 'MozMousePixelScroll') {
                var wheelDelta;

                // There's no wheelDeltaY on IE
                wheelDelta = (ev.originalEvent.wheelDeltaY)
                    ? ev.originalEvent.wheelDeltaY
                    : ev.originalEvent.wheelDelta;

                if (wheelDelta > 0) {
                    scrolledPos = $target.scrollTop() - Math.abs((wheelDelta));
                    $target.scrollTop($target.scrollTop() - Math.abs((wheelDelta / 10)));
                } else {
                    scrolledPos = $target.scrollTop() + Math.abs((wheelDelta));
                    $target.scrollTop($target.scrollTop() + Math.abs((wheelDelta / 10)));
                }
            } else {
                var scrollDelta;

                scrollDelta = (ev.type === 'MozMousePixelScroll')
                    ? ev.originalEvent.detail / 3
                    : ev.originalEvent.detail * 3;

                scrolledPos = (scrollDelta > 0)
                    ? $target.scrollTop($target.scrollTop() + Math.abs(scrollDelta))
                    : $target.scrollTop($target.scrollTop() - Math.abs(scrollDelta));
            }
        }
        
        ev.preventDefault();
    };

    /**
     * Method called when dragging a ScrollFoo scrollbar
     *
     * @param  {jQuery} $target ScrollFoo scrollbar element
     * @param  {Object} ev      Scroll event object
     */
    ScrollFoo.prototype.onMouseDownEvent = function($target, ev) {
        var initialY = ev.pageY;
        var visibleParentHeight = this.getVisibleParentHeight();
        var initialScrolledPos = parseFloat($target.css('top').replace('px', ''), 10) || 0;
        var scrollerHeight = $target.height() - (this.config.visibleParentHeightOffset / 2);

        // Adding our dragging class to the body just in
        // case we want to apply specific style overrides
        this.$body.addClass(this.config.draggingClass);

        this.$body.on('mousemove', function(e) {
            var finalScrolledPos = (e.pageY - initialY) + initialScrolledPos;

            if (finalScrolledPos <= visibleParentHeight - scrollerHeight) {
                if (finalScrolledPos < 0) {
                    finalScrolledPos = 0;
                }

                $target.css('top', finalScrolledPos);
                $(this.config.parentEl).scrollTop(finalScrolledPos / this.config.ratio);
            }
        }.bind(this));

        this.$body.one('mouseup', function() {
            this.$body.off('mousemove');
            $target.off('mouseup');

            this.$body.removeClass(this.config.draggingClass);
        }.bind(this));
    };

    ScrollFoo.prototype.getVisibleParentHeight = function() {
        if (typeof this.config.visibleParentHeight === 'function') {
            if (this.config.visibleParentHeight() === false) {
                return 'auto';
            } else {
                return this.config.visibleParentHeight() + this.config.visibleParentHeightOffset;
            }
        } else {
            return this.config.visibleParentHeight + this.config.visibleParentHeightOffset;
        }
    };

    ScrollFoo.prototype.getRealParentHeight = function() {
        if (typeof this.config.realParentHeight === 'function') {
            return this.config.realParentHeight();
        } else {
            return this.config.realParentHeight;
        }
    };

    /**
     * Calculating container height and visible content height
     * to real container height ratio, activating scrollbar if necessary
     *
     * @return {Object} ScrollFoo instance
     */
    ScrollFoo.prototype.doCalculate = function() {
        var $parentEl = $(this.config.parentEl);
        var $scrollerEl = $(this.config.scrollerEl);
        var visibleParentHeight;
        var realParentHeight;

        // Reset the visible container height, but
        // store the current scrollposition first.
        var currentScrolledPos = $parentEl.scrollTop();

        $parentEl.css({ height: 'auto' });

        visibleParentHeight = this.getVisibleParentHeight();
        realParentHeight = this.getRealParentHeight();
        this.config.ratio = visibleParentHeight / realParentHeight;

        if (this.config.ratio < 1) {
            var scrollbarHeight = this.config.ratio * visibleParentHeight;

            this.config.state = true;

            $scrollerEl
                .css({ height: scrollbarHeight })
                .removeClass(this.config.disabledClass);
        } else {
            this.config.state = false;

            $scrollerEl.addClass(this.config.disabledClass);
        }

        $parentEl
            .css({ height: visibleParentHeight })
            .scrollTop(currentScrolledPos);

        return this;
    };

    /**
     * Set the scrollbar position
     *
     * @param  {Number} scrolledPos   Amount of pixels scrolled
     * @param  {Boolean} isMouseWheel Use of mousewheel or touchpad
     * @return {Object}               ScrollFoo instance
     */
    ScrollFoo.prototype.handleReelbarScroller = function(scrolledPos, isMouseWheel) {
        var isMouseWheel = isMouseWheel || false;
        var scrollbarPos = {
            top: scrolledPos * this.config.ratio
        };

        if (isMouseWheel) {
            $(this.config.parentEl).css('top', - scrolledPos * this.config.ratio);
        }

        $(this.config.scrollerEl).css(scrollbarPos);

        return this;
    };

    scrollfoo = scrollfoo || ScrollFoo;

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = scrollfoo;
    } else if (typeof define === 'function' && define.amd) {
        define(function() {
            return scrollfoo;
        });
    } else {
        window.scrollfoo = scrollfoo;
    }
})(window.scrollfoo);
