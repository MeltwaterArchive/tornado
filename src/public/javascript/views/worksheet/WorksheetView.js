define([
	'backbone',
	'underscore',
	'spinner',
	'buzzkill',
	'mustache',
	'services/http/error-formatter',
	'views/worksheet/filter-builder',
	'hbs!templates/worksheet/worksheet',
	'hbs!templates/worksheet/sample'
], function (
	Backbone,
	 _,
	 Spinner,
	 Buzzkill,
	 Mustache,
	 ErrorFormatter,
	 FilterBuilder,
	 WorksheetTpl,
	 SampleTpl
) {

	var WorksheetView = Backbone.View.extend({

		initialize: function () {
			if (this.model) {
				this.model.on('change:posts', this.renderContent, this);
				this.model.on('change:apiError', this.showApiError, this);
			}
		},

		el: '.content-wrapper',
		header: '[data-tornado-view="page-header"]',
		content: '[data-tornado-view="page-content"]',

		events: {
			/**
			 * The create a new worksheet button is now handled in the workbook
			 * view because in this view we don't know anything about the
			 * workbook collection
			 */
			//'click .no-worksheet__action': 'addWorksheet'
			'click .fetch-more-posts__action': 'fetchMoreSuperPublicPosts',
			'click [data-worksheet-hint-toggle="filters"]': 'showFilterDetails'
		},

		/**
		 * Select the correct template to render
		 * @returns {string}
		 */
		template: function () {
			return this.model.isNew() ?
				'[data-tornado-template="no-worksheet"]' :
				'[data-tornado-template="worksheet"]';
		},

		render: function () {
			if (this.model.isNew()) {
				return this.renderNoContent();
			}
			return this.renderHeader()
				.renderContent();
		},

		/**
		 * If we have any API errors, specially rate limiting, we display those
		 * to the user.
		 */
		showApiError: function () {
			Buzzkill.notice(this.model.get('apiError'));
			this.model.set('apiError', false, {silent: true});
			this.removeSpinner();
		},

		renderHeader: function () {
			$(this.$el).find(this.header).html(WorksheetTpl({
				sample: this.model.attributes.chart_type === 'sample',
				worksheet: this.model.attributes,
				filters: FilterBuilder.filtersInfo(this.model.attributes.filters)
			}));
			return this;
		},

		/**
		 * Since we are not using the main render function we call subRender to only load the
		 * super public posts.
		 */
		renderContent: function () {
			var posts = {posts: this.model.get('posts').toJSON()};
			$(this.$el).find(this.content).html(SampleTpl(posts));
			this.removeSpinner();
			return this;
		},

		renderNoContent: function () {
			var template = $(this.template()).html();
            template = Mustache.render(template, this.model.attributes);
            this.$el.html(template);
            return this;
		},

		/**
		 * Show some UI feedback to the user when loading posts
		 */
		addSpinner: function () {
			this.blocker = $('<div data-blocker="block">' +
				'<button data-blocker-button="" style="top: 328px" type="button" data-spinner="spin">Loading</button>' +
				'</div>');
			this.$el.find('.content').append(this.blocker);
			this.$el.find('.content').removeClass('animated fadeIn');
			this.$el.find('button.fetch-more-posts__action').attr('disabled', 'disabled');
		},

		/**
		 * Removed spinner and re-enable the show more posts button
		 */
		removeSpinner: function () {
			if (!_.isEmpty(this.blocker)) {
				this.blocker.remove();
				this.$el.find('button.fetch-more-posts__action').removeAttr('disabled');
				this.$el.find('.content').addClass('animated fadeIn');
			}
		},

		/**
		 * Call the model to fetch more super public posts
		 * @returns {WorksheetView}
		 */
		fetchMoreSuperPublicPosts: function () {
			this.addSpinner();
			this.model.fetchPosts();
			return this;
		},

		showFilterDetails: function(e){
			$(this.$el).find('[data-worksheet-hint="filters"]').toggle();
		},

		/**
		 * To avoid triggering duplicated events, we call this method when changing to another worksheet,
		 * the remove disables event listeners previously added.
		 */
		remove: function(){
			this.undelegateEvents();
		}
	});

	return WorksheetView;
});