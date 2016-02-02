define([
	'backbone'
], function (Backbone) {

	var WorkbookView = Backbone.View.extend({

		el: document,

		events: {
			'workbook.locked': 'locked',
			'workbook.unlocked': 'unlocked'
		},

		forbidden: [
			'dimensions',
			'filters',
			'comparison'
		],

		initialize: function () {},

		locked: function () {

			var path = window.location.pathname;

			// add a disabled class to everything
			this.$el.find('body').addClass('disabled');
			// disabled left navigation
			this.$el.find('.main-menu a, .dropdown__content li:not([data-worksheet-action="export"]').on('click', this.handleClick.bind(this));

			/**
			 * @todo this is very hacky and will result in a nasty page jump
			 * however a user shouldn't be able to reach these pages if they are 
			 * following the standard nav.
			 */
			if (this.forbidden.indexOf(path.substr(path.lastIndexOf('/') + 1)) !== -1) {
				// bounce the user to the overview page
				window.location = window.location.origin + path.substr(0, path.lastIndexOf('/'));
			}
		},

		unlocked: function () {
			this.$el.removeClass('disabled');
			this.$el.find('.main-menu a, .dropdown__content li:not([data-worksheet-action="export"]').off('click', this.handleClick.bind(this));
		},

		handleClick: function (evt) {
			evt.stopPropagation();
			evt.preventDefault();
		}
	});

	return new WorkbookView();
});