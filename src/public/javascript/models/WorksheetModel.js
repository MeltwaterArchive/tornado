define([
	'jquery',
	'underscore',
	'backbone',
	'collections/ChartCollection',
	'collections/PostCollection',
	'collections/chart',
	'collections/workbook',
	'models/AnalyzeModel',
	'models/ChartModel'
], function ($,
			 _,
			 Backbone,
			 ChartCollection,
			 PostCollection,
			 OldChartCollection,
			 OldWorkbookCollection,
			 AnalyzeModel,
			 ChartModel) {

	/**
	 * Worksheet Model
	 *
	 * This model contains all the information for a worksheet
	 */
	var WorksheetModel = Backbone.Model.extend({

		defaults: {
			charts: new ChartCollection(),
			posts: new PostCollection(),
			chart_type: 'tornado',
			type: 'freqDist',
			comparison: 'compare',
			measurement: 'unique_authors',
			selected: false,
			display_options: {
				sort: 'label:asc',
				outliers: false
			},
			apiError: false
		},

		initialize: function () {
			if (this.get('chart_type') === 'histogram') {
				this.set('display_options', {
					sort: 'size:desc',
					outliers: false
				});
			} else {
				this.set('display_options', {
					sort: 'label:asc',
					outliers: false
				});
			}
		},

		/**
		 * URL
		 *
		 * Override the default backbone URL to use the project id. If we are
		 * update as opposed to creating we need to use the id of the worksheet
		 *
		 * @overrides Backbone.Model.url
		 *
		 * @return {string} The URL
		 */
		url: function () {
			if (!this.get('id')) {
				return '/api/project/' + this.get('project_id') + '/worksheet';
			} else {
				return '/api/project/' + this.get('project_id') + '/worksheet/' + this.get('id');
			}
		},

		/**
		 * toJSON
		 *
		 * The database model doesn't require some attributes that we require in
		 * the JS so when this model gets converted to JSON we remove those
		 * attributes
		 *
		 * @overrides Backbone.Model.toJSON
		 *
		 * @return {object}
		 */
		toJSON: function () {
			var attr = _.clone(this.attributes);
			delete attr.project_id;
			delete attr.charts;
			delete attr.selected;
			delete attr.workbook;
			delete attr.fetched;
			delete attr.interval;
			delete attr.span;
			delete attr.display_options;
			delete attr.apiError;
			delete attr.posts;
			return attr;
		},

		/**
		 * Parse
		 *
		 * When we recieve data from the services we need to make sure the
		 * charts are in a chartcollection. This parse will convert the array
		 * into a collection
		 *
		 * @overrides Backbone.Model.parse
		 *
		 * @param  {object} obj The obj from the server
		 * @return {object}
		 */
		parse: function (obj) {

			if (obj.data.charts) {
				obj.data.worksheet.charts = new ChartCollection(obj.data.charts);
			}
			if (obj.data.posts) {
				obj.data.worksheet.posts = new PostCollection(obj.data.posts);
			}

			return obj.data.worksheet;
		},

		/**
		 * Get all the params from the worksheet which we need to make the
		 * analysis query
		 *
		 * @return {[type]} [description]
		 */
		getAnalyzeParameters: function () {
			return {
				start: this.get('start') || Math.floor(Date.now() / 1000) - (32 * 24 * 60 * 60),
				end: this.get('end') || Math.floor(Date.now() / 1000) - 1,
				worksheet_id: this.get('id'),
				chart_type: this.get('chart_type'),
				type: this.get('type'),
				comparison: this.get('comparison'),
				measurement: this.get('measurement'),
				dimensions: this.get('dimensions'),
				interval: this.get('interval'),
				filters: this.get('filters')
			};
		},

		/**
		 * Analyze
		 *
		 * Perform a analyze request for this workbook
		 *
		 * @param  {object} parameters parameters to override the defaults
		 */
		analyze: function (parameters) {

			var defaults = _.extend(this.getAnalyzeParameters(), parameters),
				am = new AnalyzeModel(defaults);
			am.set(this.get('chart_type'));

			return am.save().success(function (response) {
				if (this.get('chart_type') === 'sample') {
					this.set('posts', am.get('posts'));
				} else {
					this.set('charts', am.get('charts'));
					// replace the current charts with a new chart collection
					// legacy update
					OldChartCollection.replaceByWorksheetId(response.data[0].worksheet_id, response.data);
				}
			}.bind(this)).error(function (err) {
				this.trigger('error', err);
			}.bind(this));
		},

		/**
		 * Update the options
		 *
		 * This shouldn't be needed to update the options we should just be able
		 * to patch the model. I have create a bug FTD-468 and once it is completed
		 * this can be removed and we can use
		 *
		 * this.model.save({},{patch:true});
		 *
		 * @return {[type]} [description]
		 */
		updateOptions: function (options, callback) {
			this.set({'display_options': options});
			$.ajax(this.url(), {
				type: 'PUT',
				contentType: 'application/json',
				data: JSON.stringify({
					'workbook_id': this.get('workbook_id'),
					'name': this.get('name'),
					'display_options': this.get('display_options')
				}),
				dataType: 'json'
			}).done(callback);
		},

		hydrateDimensions: function (callback) {
			this.get('workbook').getDimensions(function (err, dimensionModel) {
				var ourDimensions = [];
				this.get('dimensions').forEach(function (di) {
					ourDimensions.push(dimensionModel.getDimension(di.target));
				});

				this.set('dimensions', ourDimensions);
				callback(err, ourDimensions);
			}.bind(this));
		},

		fetchPosts: function () {
			$.ajax(this.endpoint, {
				url: this.url() + '/fetch-posts',
				type: 'GET',
				contentType: 'application/json'
			}).done(function (response) {
					var newPosts = _.isEmpty(response.data) ? [] : response.data;
					this.get('posts').add(newPosts);
					this.trigger('change:posts');
				}.bind(this))
				.fail(function (error) {
					var apiError = '! [WorksheetModel Error] ' + error.status + ': ' + error.statusText;
					if (!_.isUndefined(error.responseJSON)) {
						apiError = error.responseJSON.meta.error
					}
					this.set('apiError', apiError);
				}.bind(this));
			return this;
		}

	});

	return WorksheetModel;

});