define([], function () {


	var __months = [
		'January', 'Febuary', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 
		'October', 'November', 'December'
	];

	/**
	 * # Calender
	 *
	 * A simple google analytics insipred calender selector.
	 *
	 * ##Example
	 *
	 * var cal = new Calender(new Date(), new Date(), {
	 * 		'click'
	 * })
	 * 
	 * @param {[type]} to [description]
	 * @param {[type]} from [description]
	 */
	var Calendar = function (to, from, options) {


		var calendar = document.createElement('div'),
			html = '',
			loops = 0;

		to = this._correctDate(to);
		from = this._correctDate(from);

		loops = to.getMonth() < from.getMonth() ? to.getMonth()+12 - from.getMonth() : to.getMonth() - from.getMonth();

		for (var i = 0; i <= loops; i++) {

			var month = from.getMonth() + i,
				year = from.getFullYear();

			if (month >= 12) {
				month = month - 12;
				year += 1;
			}
			
			html += this._renderMonth(month, year);
		}

		calendar.innerHTML = html;
		calendar.className = 'calendars';

		[].slice.call(calendar.querySelectorAll('td')).forEach(function (node) {
			var nodeDate = node.getAttribute('data-date');

			if (!nodeDate) {
				node.classList.add('disable');
			}

			var d = new Date(nodeDate),
				zFrom = from,
				zTo = to;

			zFrom.setHours(0);
			zFrom.setMinutes(0);
			zFrom.setSeconds(0);
			zFrom.setMilliseconds(0);

			zTo.setHours(0);
			zTo.setMinutes(0);
			zTo.setSeconds(0);
			zTo.setMilliseconds(0);

			if (d < zFrom || d > zTo) {
				node.classList.add('disable');
			}
		});

		return calendar;
	};

	Calendar.prototype._correctDate = function (d) {
		switch (typeof d) {
			case 'number': {
				return (d + '').length > 12 ? new Date(d) : new Date (d*1000);
			}
			case 'boolean': {
				return new Date();
			}
			case 'object': {
				return d;
			}
		}
	};

	/**
	 * Render a single month in a calender
	 * 
	 * @param  {int} month 
	 * @param  {int} year 
	 * @return {string} The HTML of the calender
	 */
	Calendar.prototype._renderMonth = function (month, year) {

		var date = new Date(year, month),
			nameOfMonth = __months[date.getMonth()],
			numberOfDays = new Date(year, month + 1, 0).getDate(),
			days = ['S','M','T','W','T','F','S'],
			html = '<table cellspacing="0" cellpadding="0"><tr><th colspan="7">' + nameOfMonth + '</th></tr><tr>';

		html += '<tr>';
		// add the days of the week
		for (var i = 0; i < 7; i++) {
			html += '<td class="day">' + days[i] + '</td>';
		}
		html += '</tr>';

		// iterate through each day in the month
		for (var i = 1; i <= numberOfDays; i++) {

			var d = new Date(year, month, i),
				dow = d.getDay();

			// if we are not on a sunday we need to pad the days
			if (dow !== 0 && i === 1) {
				for (var j = 0; j < dow; j++) {
					html += '<td>&nbsp;</td>';
				}
			}

			// add the item
			html += '<td data-date="' + year + '/' + (month + 1) + '/' + i + '">' + i + '</td>';
			// pad the remaining days
			if (i === numberOfDays && dow !== 6) {
				for (var k = dow; i < 6; k++) {
					html += '<td>&nbsp;</td>';
				}
			} 

			if (dow === 6) {
				// it's a sunday so build a new row
				html += '</tr>' + (i !== numberOfDays ? '<tr>' : '');
			}
		}

		return html + '</table>';
	};

	return Calendar;
});
