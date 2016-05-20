define([
	'jquery',
	'underscore',
	'backbone',
	'moment',
	'views/sidebar/CalendarSidebarView',
	'text!templates/filters/timeframe.html'
], function ($, _, Backbone, moment, CalendarSidebarView, TimeframeTemplate) {

	'use strict';

	/**
	 * Timeframe Sidebar View
	 *
	 * This control allowes the user to alter the timeframe filter
	 *
	 * ## Options
	 * - max: The max age the recording can be (seconds)
	 * - min: The min age the recording can be (seconds)
	 * - to: The current filter on the recording end time
	 * - from: The current filter on the recording start time
	 *
	 * @todo The option object should be inherited from a model
	 * 
	 * @param  {Object} options 
	 */
	var TimeframeSidebarView = Backbone.View.extend({

		el: '.page-sidebar__section--timeframe',

		template: _.template(TimeframeTemplate),

		events: {
			'click': 'toggleDropdown',
			'click ul li': 'selectTimeframe'
		},

		/**
		 * @see TimeframeSidebarView
		 * @param  {Object} options
		 */
		initialize: function (options) {

			this.options = {
				from: moment(options.from),
				to: moment(options.to),
				min: moment(options.min),
				max: moment(options.max),
				timeframe: options.timeframe
			};

			// default timeframe filters
			this.options.timeframes = [{
				'name': 'Last 24 Hours',
				// moment mutates the original object - v. confusing!
				'time': this.options.max.clone().subtract(1, 'days')
			}, {
				'name': 'Last 7 Days',
				'time': this.options.max.clone().subtract(7, 'days')
			}, {
				'name': 'Last 2 Weeks',
				'time': this.options.max.clone().subtract(14, 'days')
			}, {
				'name': 'Last 4 Weeks',
				'time': this.options.max.clone().subtract(28, 'days')
			}];
		},

		/**
		 * Render the timeframe filter
		 */
		render: function () {

			// create the content
			var content = this.template(this);

			// add the content
			this.$el.html(content);

			if (this.calendar) {
				this.calendar.remove();
			}

			// rebind the events
			this.delegateEvents();

			// keep a reference to the dropdown
			this.dropdown = this.$el.find('ul');
			this.dataStart = this.$el.find('[name="timeframe-start"]');
			this.dataEnd = this.$el.find('[name="timeframe-end"]');

			return this;
		},

		/**
		 * Toggle Dropdown
		 *
		 * Toggle the dropdown visibilitys
		 */
		toggleDropdown: function (e) {

			if (e) {
				e.stopPropagation();
			}

			this.event = this.toggleDropdown.bind(this);

			if (this.dropdown.hasClass('visible')) {
				if (this.calendar) {
					this.calendar.remove();
				}
				this.dropdown.removeClass('visible');
			} else {
				this.dropdown.addClass('visible');
				//$(document.body).one('click', this.event);
			}
		},

		/**
		 * Select Timeframe
		 *
		 * When a user selects a timeframe we have to update the two data
		 * attributes
		 * 
		 * @param  {Event} evt  Click event
		 */
		selectTimeframe: function (evt) {

			var lis = this.$el.find('li'),
				target = $(evt.currentTarget);

			// if the dropdown is already visible shortcircuit
			if (!this.dropdown.hasClass('visible') || target.hasClass('placeholder')) {
				return;
			}

			// stop the event from bubbling up to the parent click event
			evt.stopPropagation();

			// if this is the custom event show the calendar
			if (target.hasClass('custom')) {
				this.showCalendar();
				return;
			}

			// select the correct item
			lis.removeClass('selected');
			target.addClass('selected');
			this.toggleDropdown();

			// because we are working out these times from UTC times these don't
			// need to be converted
			this.options.from = moment(parseInt(target.attr('data-start'), 10));
			this.options.to = moment(parseInt(target.attr('data-end'), 10));
			this.options.timeframe = target.attr('data-name');
			this.render();
		},

		/**
		 * Show Calendar
		 *
		 * Create a new calendar view which allowes the user to select a 
		 * custom timeframe
		 */
		showCalendar: function () {
			this.calendar = new CalendarSidebarView({
				min: this.options.min,
				max: this.options.max,
				start: this.options.from || this.options.min, 
				end: this.options.to || this.options.max, 
				element: this.$el
			}, function (start, end) {
				if (start && end) {
					// these are user based times so need to be converted
					this.options.from = moment.utc(start);
					this.options.to = moment.utc(end);
					this.options.timeframe = 'custom';
				}
				$(document.body).off('click', this.event);
				this.render();
			}.bind(this));
			this.calendar.render();
		}
	});

	return TimeframeSidebarView;
});