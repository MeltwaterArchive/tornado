define([
	'jquery',
	'underscore',
	'backbone'
], function ($, _, Backbone) {
	/**
	 * A misc popup view which will render a nice little dialog box for us
	 *
	 * <h2>Usage</h2>
	 */
	var PopupView = Backbone.View.extend({

		// events to apply
		events: {
			'click .close': 'removePopup'
		},

		// Popup template
		template: _.template(
			'<div id="popup" class="<%= typeof(classname) !== \'undefined\' ? classname : \'\' %>">' +
				'<div class="popup-header">' +
					'<h6><%= typeof (title) !== \'undefined\' ? title : \'\' %></h6>' +
					'<div class="close"></div>' +
				'</div>' +
				'<div class="body">' +
					'<%= body %>' +
				'</div>' +
			'</div>'
        ),

		/**
		 * Render the popup
		 * 
		 * @param  {String} title title for the popup
		 * @param  {String} body  string to append to the popup
		 */
        render: function (body, attributes) {

			// run through the template once
			var template = this.template(_.extend({
				'body': body
			}, attributes));
			// now run through it again with our new attributes
			this.el.innerHTML = _.template(template)(attributes || {});
			this.el.id = 'overlay';
			this.attributes = attributes;

			document.body.appendChild(this.el);

			var input = $('input[type="text"]', this.el);

			if (input.length > 0) {
				input[0].focus();
			}

			setTimeout(function () {
				// add an active class to give it a nice animation
				this.$el.find('#popup').addClass('active');
			}.bind(this), 100);

			return this.el;
        },

        remove: function () {
        	this.$el.remove();
        },

        removePopup: function (e) {
			if ($(e.target).hasClass('close') || e.target.id === 'overlay') {
				this.remove();
			}
        }
	});

	return PopupView;
});