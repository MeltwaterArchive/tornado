define([
	'config',
	'jquery',
	'underscore',
	'backbone',
	'plugins/scrollfoo',
	'models/EventModel',
	'text!templates/worksheet/sidebar/dimensions/dimensions.html',
	'text!templates/worksheet/sidebar/dimensions/item.html'
], function (
	config,
	$,
	_,
	Backbone,
	ScrollFoo,
	EventModel,
	DimensionsTemplate,
	DimensionsItemTemplate
) {

	'use strict';

	/**
	 * Dimension Sidebar
	 *
	 * Drag and drop interface for editing a worksheet
	 */
	var DimensionsSidebarView = Backbone.View.extend({
		
		el: '[data-tornado-view="page-sidebar"]',

		template: _.template(DimensionsTemplate),

		events: {
			'keyup .dimensions-search': 'search'
		},

		/**
		 * Initilize
		 *
		 * Bind the event listeners for the drop and drag events since these 
		 * are now in the dimensionsView and not in this one.
		 */
		initialize: function () {
			this.workbook = this.model.get('workbook');
			EventModel.on('dimensionDrop', this.dimensionDrop.bind(this));
			EventModel.on('dimensionRemove', this.dimensionRemove.bind(this));
		},

		/**
		 * Render
		 *
		 * Render the template, this is just the outertemplate
		 */
		render: function () {
            this.$el.html(this.template());
			this.workbook.getDimensions(function (err, dimensionModel) {
				this.dimensionModel = dimensionModel;
				this.subRender();
				// small delay to finish the rendering before we bind
				setTimeout(this.bind.bind(this), 500);
			}.bind(this));
		},

		/**
		 * Bind
		 *
		 * Attach all the event handlers
		 */
		bind: function () {
			// give the search box focus
			this.$el.find('.dimensions-search').focus();
			// bind the scroll bar
			this.scrollfoo = new ScrollFoo({
				parentEl: '.scrollfoo__parent--dimensions',
                scrollerEl: '.scrollfoo__scroller--dimensions',
                visibleParentHeight: function() {
                    return window.innerHeight - 
                    	$('.scrollfoo__parent--dimensions').offset().top;
                }.bind(this),
                realParentHeight: function() {
                    return $('.scrollfoo__parent--dimensions').outerHeight();
                }
			});
		},

		/**
		 * Sub Render
		 *
		 * This will rerender the dimensions
		 * 
		 * @param  {array} dimensions
		 */
		subRender: function (d) {
			var template = _.template(DimensionsItemTemplate);

			d = d || this.dimensionModel;

			// if we have dimensions already set
			if (this.model.get('dimensions').length > 0) {
				// if any of those have a high cardinality, restrict the list
				this.model.get('dimensions').forEach(function (dimension) {
					if (dimension.cardinality > config.highCardinality || !dimension.cardinality) {
						d = d.filterCardinality();
					}
				}.bind(this));

				// remove any dimensions already in the query
				d = d._unflatten(d._flatten().filter(function (d) {
					var rtn = true;
					// look through all the current dimensions
					this.model.get('dimensions').forEach(function (d2) {
						// if any match the target
						if (d.target === d2.target) { rtn = false; }
					});
					return rtn;
				}.bind(this)));

				// hydrate the groups after our filtering because we want to 
				// show empty groups to the user
				d = d.hydrateGroups();
			}
			
			// render the template
			template = template(d.attributes);
			this.$el.find('.scrollfoo__parent--dimensions').html(template);
		},

		/**
		 * Search
		 *
		 * When a user searches the dimension list is filtered
		 * 
		 * @param  {event} evt
		 */
		search: function (evt) {
			var searchTerm = evt.target.value.toLowerCase();
			this.subRender(this.dimensionModel.filterLabel(searchTerm, true));
			this.scrollfoo.doCalculate();
		},

		/**
		 * Dimension Drop
		 *
		 * When we drop a new dimension into the dropzone we want to update the
		 * model with the new dimension.
		 *
		 * @todo The DimensionView should also manipulate the model and we
		 * should be listening to an 'change' event.
		 * 
		 * @param  {object} dimension
		 */
		dimensionDrop: function (dimension) {
			var dimensions = _.clone(this.model.get('dimensions'));
			dimensions.push(dimension);
			this.model.set('dimensions', dimensions);
			this.subRender();
		},

		/**
		 * Dimension Remove
		 *
		 * When we remove the dimension we need to clone the object first and
		 * then set it back to the model
		 * 
		 * @param  {object} dimension
		 */
		dimensionRemove: function (dimension) {
			var dimensions = _.clone(this.model.get('dimensions'));
			dimensions = dimensions.filter(function (d) {
				if (d.target === dimension.target) {
					return false;
				}
				return true;
			});
			this.model.set('dimensions', dimensions);
			this.subRender();
		}
	});

	return DimensionsSidebarView;
});