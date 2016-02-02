define(['jquery'], function($) {
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    var Tooltip = function() {
        this.el = {
            tooltip: '.tooltip',
            trigger: '[data-tooltip]'
        };

        this.classes = {
            tooltip: 'tooltip',
            tooltipActive: 'tooltip--active'
        };

        this.attributes = {
            tooltip: 'tooltip'
        };

        this.enabled = true;
        this.offsetTop = 0;
        this.offsetLeft = 0;
        this.scrollY;

        this.initialize();
    };

    Tooltip.prototype.initialize = function() {
        this.createTooltipElement();
        this.registerEvents();
    };

    Tooltip.prototype.registerEvents = function() {
        var _this = this;

        $(window).scroll(function() {
            _this.scrollY = $(window).scrollTop();
        });

        $('body').on('mouseenter.tooltip', this.el.trigger, function(ev) {
            if (_this.enabled) {
                _this.constructAndShow($(this));
            }
        });

        $('body').on('mouseleave.tooltip', this.el.trigger, function(ev) {
            $(_this.el.tooltip).removeClass(_this.classes.tooltipActive);
        });
    };

    Tooltip.prototype.createTooltipElement = function() {
        var el = document.createElement('div'); // faster than jQ equivalent, though probably negligible

        el.className = this.classes.tooltip;
        document.body.appendChild(el);
    };

    Tooltip.prototype.constructAndShow = function($target) {
        var $tooltip = $(this.el.tooltip);
        var targetRect = $target[0].getBoundingClientRect();

        $tooltip
            .attr('class', this.classes.tooltip)
            .html($target.data(this.attributes.tooltip))
            .css({
                top: targetRect.top - $tooltip.outerHeight() - this.offsetTop,
                left: targetRect.left - ($tooltip.outerWidth() / 2) + (targetRect.width / 2) + this.offsetLeft
            })
            .addClass(this.classes.tooltipActive);
    };

    Tooltip.prototype.hide = function() {
        $(this.el.tooltip).removeClass(this.classes.tooltipActive);
    };

    Tooltip.prototype.enable = function() {
        this.enabled = true;
    };

    Tooltip.prototype.disable = function() {
        this.enabled = false;

        this.hide();
    };

    instance = new Tooltip();

    return instance;
});
