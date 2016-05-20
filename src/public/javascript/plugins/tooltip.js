define([
    'jquery',
    'bootstrap/ToolTip'
], function($, ToolTip) {

    'use strict';

    var SingleToolTip = function (el) {
        this.$el = $(el);
        this.event = this.destroy.bind(this);
        //this.$el.on('mouseover', this.render.bind(this));
        this.$el.on('mouseout', this.event);
        this.render();
    };

    SingleToolTip.prototype.render = function () {

        if (this.tooltip || !this.$el.data('tooltip')) {
            // we don't want to create more than one
            return;
        }

        this.content = this.$el.attr('data-tooltip');

        var content = this.content.split(' ');

        // parse the urls
        content = content.map(function (word) {

        	// matches a domain
        	var domain = /^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/,
                parsedStr = word.substr(0, word.length-1);

        	if (word.indexOf('http') !== -1) {
                return '<a href="' + word + '" target="_blank">' + word + '</a>';
            } 

        	// check for domains
            if (domain.test(parsedStr)) {
            	// probably a domain
        		return '<a href="http://' + word + '" target="_blank">' + word + '</a>';
        	}
        	// return the work
        	return word;
        });

        this.tooltip = new ToolTip(content.join(' '), this.$el[0]);
    };

    SingleToolTip.prototype.destroy = function (e) {

        if (!this.tooltip) {
            return;
        }

        if (e.toElement !== this.tooltip.getToolTip()) {
            this.tooltip = this.tooltip.remove();
            // remove the element
            this.$el.off('mouseout', this.event);
        } else {
            this.tooltip.getToolTip().addEventListener('mouseout', function (e) {
                if (e.toElement !== this.$el[0] && !this.tooltip.isDescendant(e.toElement)) {
                    this.tooltip = this.tooltip.remove();
                    // remove the element
                    this.$el.off('mouseout', this.event);
                }
            }.bind(this));
        }
    };

    $('body').on('mouseover', '[data-tooltip]', function (e) {

        if ($(e.target).attr('data-draggaball-item') === 'clone') {
            return;
        }

        new SingleToolTip(e.target);
    });
});
