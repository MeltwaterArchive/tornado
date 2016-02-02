define([
	'jquery',
	'backbone',
	'spinner'
], function ($, Backbone) {

	var SidebarView = Backbone.View.extend({
		'el': '[data-tornado-view="page-sidebar"]',


		loadingStart: function (callback) {
			// block out the content
			this.blocker = $('<div data-blocker="block"><button data-blocker-button="" style="top: 328px" type="button" data-spinner="spin">Loading</button></div>');
			this.$el.append(this.blocker);

			/**
			 * Horrible/nasty way of doing it
			 *
			 * Ideally each of teh controllers should load content into this
			 * view and therefore can call the loadingStop
			 *
			 * Until I get around to rewritting the way the controllers work
			 * this will have to do.
			 *
			 * @todo
			 */
			this.$el.bind("DOMSubtreeModified", function () {
				this.loadingStop();
				callback();
			}.bind(this));

			// now start the spinner
			//this.$el.attr('data-spinner', 'spin');
		},

		loadingStop: function () {
			// make sure the parent node exists this was causing a recursion 
			// error in FF
			if (this.blocker && this.blocker.first.parentNode) {
				this.blocker.remove();
			}

			this.$el.unbind();
		}
	});

	return new SidebarView();
});
