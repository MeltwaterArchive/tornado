define(['jquery', 'underscore'],
function($, _) {
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Dropdown Toggle
     *
     * @singleton
     */
    var DropdownToggle = function() {
        this.el = {
            dropdownContent: '.dropdown__content',
            toggleButton: '[data-dropdown-toggle]'
        };

        this.classes = {
            dropdownContent: 'dropdown__content',
            dropdownContentOpen: 'dropdown__content--open'
        };

        this.attributes = {
            dropdownButton: 'data-dropdown-toggle'
        };

        this.bindEvents();
    };

    DropdownToggle.prototype.bindEvents = function() {
        var _this = this;

        $('body').on('click.dropdown', this.el.toggleButton, function(ev) {
            ev.stopPropagation();

            _this.toggle($(this).siblings(_this.el.dropdownContent));
        });

        $('body').on('click.dropdown', function(ev) {
            var $target = $(ev.target);

            if (!$target.hasClass(_this.classes.dropdownContent) &&
                _.isUndefined($target.attr(_this.attributes.dropdownButton)) &&
                $target.parent(_this.el.dropdownContent).length === 0) {
                _this.closeAll();
            }
        });
    };

    DropdownToggle.prototype.open = function($dropdown) {
        $dropdown.addClass(this.classes.dropdownContentOpen);
    };

    DropdownToggle.prototype.close = function($dropdown) {
        $dropdown.removeClass(this.classes.dropdownContentOpen);
    };

    DropdownToggle.prototype.closeAll = function() {
        this.close($(this.el.dropdownContent));
    }

    DropdownToggle.prototype.toggle = function($dropdown) {
        if ($dropdown.hasClass(this.classes.dropdownContentOpen)) {
            this.close($dropdown);
        } else {
            this.open($dropdown);
        }
    };

    instance = new DropdownToggle();

    return instance;
});
