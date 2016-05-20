define([], function () {
	'use strict';

	/**
	 * Error Handler
	 *
	 * Create a new error message.
	 *
	 * ##Usage
	 *
	 * new ErrorView('Your password is incorrect').tooltip(<)
	 * 
	 * @param {[type]} message [description]
	 */
	var ErrorView = function (message) {
		this.message = message;
	};

	ErrorView.prototype.tooltip =  function (element, position) {
		
		var directions = ['auto', 'top', 'bottom', 'left', 'right'],
			bodyRect = document.body.getBoundingClientRect(),
			elementRect = element.getBoundingClientRect(),
			offsetTop = elementRect.top - bodyRect.top,
			offsetLeft = elementRect.left - bodyRect.left;

		// default the position to auto
		position = position || 'auto';
		
		if (directions.indexOf(position) === -1) {
			throw 'Position is not valid, please choose either auto, top, bottom, left or right';
		}

		if (position === 'auto') {
			throw 'Auto is not yet implemented';
		}

		// create the element
		this.tooltip = document.createElement('div');
		this.tooltip.classList.add('error-tooltip', position);
		// populate the content
		this.tooltip.innerHTML = 
			'<div class="close"></div><div class="message">' + 
			this.message + '</div>';

		// need to append the item to calculate the height
		document.body.appendChild(this.tooltip);
		// adjust the position depending on where we want it
		switch(position) {
			case 'top':
				offsetTop -= this.tooltip.offsetHeight + 6;
				break;
			case 'left':
				offsetLeft -= this.tooltip.offsetWidth + 6;
				break;
			case 'right':
				offsetLeft += element.offsetWidth - 6;
				break;
			case 'bottom':
				offsetTop += element.offsetHeight - 6;
				break;

		}
		// apply to the tooltip
		this.tooltip.style.left = offsetLeft + 'px';
		this.tooltip.style.top = offsetTop + 'px';

		// add an event listener to remove
		// need to cache the removal event
		this.removeEvent = this.remove.bind(this);
		//document.body.addEventListener('click', this.removeEvent);
		//
		return this;
	};

	ErrorView.prototype.remove = function () {
		//if (this.tooltip && this.tooltip.parentNode) {
			this.tooltip.parentNode.removeChild(this.tooltip);
			//document.body.removeEventListener('click', this.removeEvent);
			return null;
		
	};

	return ErrorView;

});

