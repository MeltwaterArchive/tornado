define([
	'jquery',
	'underscore',
	'backbone',
	'moment',
	'libs/calendar',
	'views/error/ErrorHandler',
	'views/popup/PopupView',
	'text!templates/filters/calendar.html'
], function (
	$, 
	_, 
	Backbone, 
	moment, 
	Calendar, 
	ErrorHandler, 
	PopupView, 
	CalendarTemplate
) {

	'use strict';

	var CalendarSidebarView = Backbone.View.extend({

		template: _.template(CalendarTemplate),

		events: {
			'click': 'bubbly',
			'click td[data-date]': 'selectDate',
			'click .submit-button': 'submit',
			'click .clear-button': 'reset',
			'keyup input[type="text"]': 'change'
		},

		/**
		 * Calendar Selector
		 *
		 * This enables the user to select a time range filter using the 
		 * calendar control
		 *
		 * Options
		 * - min, when the recording started (the min possible number)
		 * - max, then the recording ended (the max possible number)
		 * - start, the current filter start time
		 * - end, the current filter end time
		 * - element, the element to attach to
		 * 
		 * @param  {Object}   options
		 * @param  {Function} callback
		 */
		initialize: function (options, callback) {
			// we are asking the user to edit these so we need to make sure
			// they are in the users local timeframe, so we are converting
			// from UTC
			this.min = moment.utc(options.min).local();
			this.max = moment.utc(options.max).local();
			this.start = moment.utc(options.start.valueOf() || options.min).local();
			this.end = moment.utc(options.end.valueOf() || options.max).local();

			this.element = options.element;
			this.timespan = [this.start, this.end];
			this.callback = callback;

			// if the current date is older than 32 days
			if (this.start.seconds() < moment().subtract(32, 'days').seconds()) {
				// restrict it to the last 32 days
				this.start = moment().subtract(32, 'days');
			}
		},

		/**
		 * This will stop the events from bubbling up
		 * @param  {[type]} e [description]
		 * @return {[type]}   [description]
		 */
		bubbly: function (e) {
			e.stopPropagation();
		},

		/**
		 * Render
		 *
		 * Render the calendar
		 */
		render: function () {

			this.popup = new PopupView();
			this.el = this.popup.render(this.template(this), {
				'title': 'Custom Timeframe',
				'classname': 'calendar' 
			});
			this.$el = $(this.el);

			// add the calendars
			this.$el.find('.calendar-wrapper').append(
				new Calendar(moment(this.max).toDate(), moment(this.min).toDate())
			);

			// if we are showing 3 calendars make it wider
			if (this.$el.find('.calendar-wrapper table').length >= 3) {
				this.$el.addClass('bigger');
			}

			// rebind the events
			this.delegateEvents();

			// add the default dates
			this.addDate(this.start);
			this.addDate(this.end);

			return this;
		},

		/**
		 * Change
		 *
		 * When a user attempts to change the text boxes, either the time or 
		 * the date we need to validate their input. This forces the user 
		 * to enter the time in a UTC format.
		 */
		change: function () {

			var start = moment(this.getStart(), 'YYYY-MM-DD hh:mm', true),
				end = moment(this.getEnd(), 'YYYY-MM-DD hh:mm', true);


			// remove all the old errors
			if (this.fromValidError) this.fromValidError = this.fromValidError.remove();
			if (this.fromLessError) this.fromLessError = this.fromLessError.remove();
			if (this.fromGreaterError) this.fromGreaterError = this.fromGreaterError.remove();
			if (this.toLessError) this.toLessError = this.toLessError.remove();
			if (this.toGreaterError) this.toGreaterError = this.toGreaterError.remove();

			
			// if the date isn't valid
			if (!start.isValid()) {
				// throw an error
				this.$el.find('.controls .from').addClass('errored');
				this.$el.find('.submit-button').addClass('disabled');
				this.fromValidError = new ErrorHandler('The start date must be in the format YYYY-MM-DD HH:MM')
					.tooltip(this.$el.find('input[name="from-date"]')[0], 'top');
				return;
			}

			// if it's less than the min
			if (start < this.min) {
				// throw an error
				this.$el.find('.controls .from').addClass('errored');
				this.$el.find('.submit-button').addClass('disabled');
				this.fromLessError = new ErrorHandler('The start date cannot be before ' + moment(this.min).format('YYYY-MM-DD HH:MM'))
					.tooltip(this.$el.find('input[name="from-date"]')[0], 'top');
				return;
			}

			// if it's greater than the max
			if (start > this.max && !this.fromGreaterError) {
				// throw an error
				this.$el.find('.controls .from').addClass('errored');
				this.$el.find('.submit-button').addClass('disabled');
				this.fromGreaterError = new ErrorHandler('The start date cannot be after ' + moment(this.max).format('YYYY-MM-DD HH:MM'))
					.tooltip(this.$el.find('input[name="from-date"]')[0], 'top');
				return;
			}

			// if the date isn't valid, or it's greater than our end time
			if (!end.isValid() && !this.toValidError) {
				this.$el.find('.controls .to').addClass('errored');
				this.$el.find('.submit-button').addClass('disabled');
				this.toValidError = new ErrorHandler('The end date must be in the format YYYY-MM-DD HH:MM')
					.tooltip(this.$el.find('input[name="to-date"]')[0], 'top');
				return;
			}

			// if it's less than the min
			if (end < this.min && !this.toLessError) {
				// throw an error
				this.$el.find('.controls .from').addClass('errored');
				this.$el.find('.submit-button').addClass('disabled');
				this.toLessError = new ErrorHandler('The end date cannot be before ' + moment(this.min).format('YYYY-MM-DD HH:MM'))
					.tooltip(this.$el.find('input[name="to-date"]')[0], 'top');
				return;
			}

			// if it's greater than the max
			if (end > this.max && !this.toGreaterError) {
				// throw an error
				this.$el.find('.controls .from').addClass('errored');
				this.$el.find('.submit-button').addClass('disabled');
				this.toGreaterError = new ErrorHandler('The end date cannot be after ' + moment(this.max).format('YYYY-MM-DD HH:MM'))
					.tooltip(this.$el.find('input[name="to-date"]')[0], 'top');
				return;
			}

			this.$el.find('.controls .from, .controls .to').removeClass('errored');
			this.$el.find('.submit-button').removeClass('disabled');

			this.addDate(start);
			this.addDate(end);
		},

		/**
		 * Submit
		 *
		 * Save the filter and call the callback
		 */
		submit: function () {

			var start = this.getStart(),
				end = this.getEnd();

			// if we have disabled the submit button don't do anything
			if (this.$el.find('.submit-button').hasClass('disabled')) {
				return;
			}

			start = moment(start);
			end = moment(end);

			this.callback(start.valueOf(), end.valueOf());
		},

		/**
		 * Reset
		 *
		 * Reset this dialog back to the default max and min
		 */
		reset: function () {
			// update the timespans
			this.addDate(this.min);
			this.addDate(this.max);

			// update the dates
			this.$el.find('input[name="from-date"]').val(this.timespan[0].format('YYYY-MM-DD'));
			this.$el.find('input[name="to-date"]').val(this.timespan[1].format('YYYY-MM-DD'));

			// update the times
			this.$el.find('input[name="from-time"]').val(this.start.format('hh:mm'));
			this.$el.find('input[name="to-time"]').val(this.end.format('hh:mm'));
		},

		/**
		 * Get Start
		 *
		 * Get the start time and date
		 */
		getStart: function () {
			return this.$el.find('input[name="from-date"]').val() + 
				' ' + this.$el.find('input[name="from-time"]').val();
		},

		/**
		 * Get End
		 *
		 * Get the end time and date
		 */
		getEnd: function () {
			return this.$el.find('input[name="to-date"]').val() + 
				' ' + this.$el.find('input[name="to-time"]').val();
		},
		
		/**
		 * Select Date
		 *
		 * When a user select a date timeframe update the classes and call
		 * add date accordingly
		 * 
		 * @param  {Event} e Click Event
		 */
		selectDate: function (e) {

			var date = moment($(e.target).attr('data-date'), 'YYYY-MM-DD'),
				start = this.getStart(),
				end = this.getEnd();

			if (date === undefined || $(e.target).hasClass('disable')) {
				return;
			}

			// make sure we create a correct event
			this.change();

			// update the timespans
			this.addDate(date);

			// update the dates
			this.$el.find('input[name="from-date"]').val(this.timespan[0].format('YYYY-MM-DD'));
			this.$el.find('input[name="to-date"]').val(this.timespan[1].format('YYYY-MM-DD'));

			// update the times
			//this.$el.find('input[name="from-time"]').val(this.start.format('hh:mm'));
			//this.$el.find('input[name="to-time"]').val(this.end.format('hh:mm'));

			// call the change event to remove any errors we have
			this.change();
		},

		/**
		 * #dateSpan
		 * 
		 * Provides all the logic for the selecting of dates on the calendar.
		 *
		 * It uses an array, `timespan` to control the start and end dates. We 
		 * actually have 3 states of time. The first state is the start time, if 
		 * this is selected we automatically fill the second state to be the 
		 * same. Thus spanning one day. When the user then selects the next date 
		 * this is updated with the end date.
		 *
		 * @param  {[type]} time [description]
		 * @return {[type]} [description]
		 */
		addDate: function (time) {

			if (this.timespan.length > 0) {
				// should be two items
				if (this.flag) {
					this.timespan[1] = time;
					// reset the flag
					this.flag = false;
				} else {
					this.timespan = [];
				}
			}

			// if the first item is after the second flip the array
			if (this.timespan.length > 0) {
				var a = this.timespan[0],
					b = this.timespan[1];

				if (a > b) {
					this.timespan.reverse();
				}
			}

			if (this.timespan.length === 0) {
				this.timespan.push(time);
				this.timespan.push(time);
				this.flag = true;
			}

			if (this.timespan.length === 2) {
				var selectedSwitch = false;
				this.$el.find('td[data-date]').removeClass('selected');
				// select all the elements in between
				this.$el.find('td[data-date]').each(function (i, d) {
					if ($(d).attr('data-date') === this.timespan[0].format('YYYY/M/D')) selectedSwitch = true;
					if (selectedSwitch) $(d).addClass('selected');
					if ($(d).attr('data-date') === this.timespan[1].format('YYYY/M/D')) selectedSwitch = false;
				}.bind(this));
			}
		}
	});

	return CalendarSidebarView;
});