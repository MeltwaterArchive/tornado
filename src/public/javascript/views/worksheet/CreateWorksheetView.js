define([
	'config',
	'underscore',
	'jquery',
	'backbone',
	'chosen',
	'router',
	'buzzkill',
	'services/http/error-formatter',
	'views/popup/PopupView',
	'collections/workbook',
	'collections/chart',
	'models/WorkbookModel',
	'models/WorksheetModel',
	'text!templates/worksheet/create.html'
], function (
	config,
	_,
	$,
	Backbone,
	Chosen,
	Router,
	Buzzkill,
	ErrorFormatter,
	PopupView,
	WorkbookCollection,
	ChartCollection,
	WorkbookModel,
	WorksheetModel,
	CreateTemplate
) {

	'use strict';

	/**
	 * Create a Worksheet
	 *
	 * This uses the new "Natural Language UI" which is a series of select boxes
	 * in order for the user to build their worksheets
	 *
	 * @param  {Int} projectId    The project ID
	 * @param  {Int} workbookId   The workbook ID
	 */
	var CreateWorksheetView = Backbone.View.extend({

		events: {
			'change select': 'selectChange',
			'click .submit-button': 'submit',
			// may need to destroy the current instance before doing this
			'click .clear-button': 'reset',
			'click .add': 'showDropdown'
		},

		template: _.template(CreateTemplate),

		dimensions: [],
		query: [],
		chartType: 'breakdown',

		timeframeDimensions: {
			attributes: {
				groups: [{
					'name': 'Timeframe',
					'items': [
						{
							'target': 'hour',
							'label': 'Hourly'
						},
						{
							'target': 'day',
							'label': 'Daily'
						},
						{
							'target': 'week',
							'label': 'Weekly'
						},
						{
							'target': 'minute',
							'label': 'Minute'
						}
					]
				}]
			}

		},

		timeframeLimits: {
			'minute': 3600, // one hour
			'hour': 1209600, // 336 hours
			'day': 2764800, // 32 days
			'week': 2419200 // 4 weeks
		},

		/**
		 * @see Class defintion
		 */
		initialize: function (projectId, workbook) {
			// are we passing a worksheet model
			if (projectId instanceof Backbone.Model) {
				this.model = projectId;
			} else {
				// lets create a new model with the old parameters
				this.model = new WorksheetModel({
					project_id: arguments[0],
					workbook_id: arguments[1]
				});
			}
			// are we passing a workbook model
			if (workbook instanceof Backbone.Model) {
				this.workbook = workbook;
			} else {
				// we need to fetch it
				this.workbook = new WorkbookModel(WorkbookCollection.getModel(this.model.get('workbook_id')));
			}

			this.bind();
		},

		bind: function () {
			this.model.on('error', this.error.bind(this));
			this.model.on('change:charts', this.removeModal.bind(this));
			this.model.on('change:posts', this.removeModal.bind(this));
		},

		removeModal: function () {
			if (this.popup) {
				this.popup.remove();
			}
			Router.navigateTo('/projects/' + this.model.get('project_id') + '/worksheet/' + this.model.get('id'));
		},

		/**
		 * Render
		 *
		 * Build a popup and add a loading spinner
		 */
		render: function () {

			this.popup = new PopupView();

			this.el = this.popup.render(this.template({}), {
				'title': 'New Worksheet',
				'classname': 'create-worksheet'
			});
			// have to redefine the JQuery version of el (YUK!)
			this.$el = $(this.el);
			// add a loading spinner
			this.$el.find('.body').addClass('loading-spin blue');

			this.workbook.getDimensions(function (err, dimensions) {
				this.dimensions = dimensions;

				if (!this.dimensions.getDimension('time')) {
					// add the time back into the dimensions
					this.dimensions.addDimension({
						target: 'time',
						group: 'Other',
						label: 'Time'
					});
				}

				this.subRender();
				// small delay to finish the rendering before we bind
				setTimeout(this.bind.bind(this), 500);
				// remove spinner
				this.$el.find('.body').removeClass('loading-spin blue');
			}.bind(this));

			return this;
		},

		/**
		 * Sub Render
		 *
		 * Render the contents of the popup
		 */
		subRender: function () {

			var filteredDimensions = [],
				fd = [this.dimensions],
				showNext = true;

			// add a special case for filtering on time
			if (this.query[0] === 'time') {
				fd = [this.dimensions, this.timeframeDimensions];
				// time can only have 2 queries
				if (this.query.length === 2) {
					showNext = false;
				}
			} else {
				this.query.forEach(function (q, i) {
					fd.push(this.dimensions.filterAllowability(this.query.slice(0, i+1)));
				}.bind(this));
			}

			// rerender the body with the updated sections
			this.$el.find('.body').html(this.template({
				"chartType": this.chartType,
				'query': this.query,
				'dimensions': fd,
				'dimensionsFiltered': filteredDimensions,
				'showNext': showNext
			}));

			// rebind the events
			this.delegateEvents();
			// rebind chosen
			this.$el.find('select:not(.hidden)').chosen({
				allow_single_deselect: true
			}).change(this.selectChange.bind(this));
		},

		/**
		 * Select Change
		 *
		 * When one of the dropdown changes state we will rerender the center
		 * of the popup. This is a lot easier to maintain than to manage
		 * the selects independantly.
		 *
		 * @param  {Event} evt onChange event
		 */
		selectChange: function (evt) {
			// clear the current query
			this.query = [];

			// loop through the select boxes adding new items
			this.$el.find('.body select').each(function (i, select) {
				if (select.value && select.className !== 'chart-types') {
					this.query.push(select.value);
				}
				if(select.className == 'chart-types'){
					this.chartType = select.value;
				}
			}.bind(this));

			this.subRender();
		},

		/**
		 * Show Dropdown
		 *
		 * When the user clicks the plus on the next element show the next
		 * dropdown
		 *
		 * @param  {event} e
		 */
		showDropdown: function (e) {
			var target = $(e.target);
			target.hide();
			target.next().show();
			target.next().chosen({
				allow_single_deselect: true
			}).change(this.selectChange.bind(this));
		},

		/**
		 * Submit
		 *
		 * Build the worksheet and then make the analyze query. The analyze
		 * query may come back with an error we want to make sure we don't
		 * create a new worksheet again so we check to make sure we don't
		 * already have a worksheet id
		 *
		 * @todo we may want to investigate destorying the worksheet if the
		 * user abandons the create dialog
		 */
		submit: function () {

			// add a loading spinner
			this.$el.find('.submit-button').attr('data-spinner', 'spin');

			// if we have no dimensions
			if (this.query.length < 1 && this.$el.find('select.chart-types').val() != 'sample') {
				Buzzkill.notice('Please select a dimension');
				return;
			}

			// add the new attributes
			this.model.set({
				chart_type: this.query.length >= 2 ? 'tornado' : 'histogram',
				type: this.query[0] === 'time' ? 'timeSeries' : 'freqDist',
				name: this.buildWorksheetTitle()
			});

			// are we dealing with a timeseries
			if (this.query[0] === 'time') {
				this.model.set({
					'chart_type': 'timeseries',
					'start': Math.floor(Date.now() / 1000) - this.timeframeLimits[this.query[1]],
					'interval': this.query[1]
				});
			}

			// sort the query by cardinality
			if (this.query[0] !== 'time') {

				this.query.sort(function (a, b) {
					var carA = this.getInfo(a),
						carB = this.getInfo(b);

					if (carA.cardinality === undefined) {
						carA.cardinality = Infinity;
					}

					if (carB.cardinality === undefined) {
						carB.cardinality = Infinity;
					}

					return carA.cardinality - carB.cardinality;
				}.bind(this));

				// add the dimensions to the request, deal with the odd format
				// of the dimensions object
				this.model.set('dimensions', this.query.map(function (t) {
					return {'target': t};
				}));

				if (this.chartType === 'sample') {
					delete this.model.attributes.dimensions;
					this.model.set({
						'chart_type': this.chartType,
						'type': this.chartType
					});
				}
			}

			/**
			 * Take a copy of the analyse object. Ideally we shouldn't have to
			 * do this. However because the server won't accept the model with
			 * a dimensions attribute, we have to strip it out. Then when we
			 * sync it isn't there. So we have to make a copy of it
			 *
			 * @annoying
			 */
			var analyzeObj = this.model.getAnalyzeParameters();

			// is the model new
			if (this.model.isNew()) {
				this.model.save().success(function (response) {
					/**
					 * @todo this is not the way collections work, we need to
					 * remove this dependency
					 */
					var workbook = WorkbookCollection.getModel(this.model.get('workbook_id'));
					WorkbookCollection.addWorksheetToWorkbook(workbook, this.model.attributes);
					$('body').trigger('createworksheet.project', [{
	                    worksheet: response.data.worksheet
	                }]);
	                // close the workbook
	                $('body').trigger('close.workbooksidebar');

	                // run the analyse, pass through a new id because our copy
	                // doesn't have an id
					this.model.analyze(_.extend(analyzeObj, {
						'worksheet_id': this.model.get('id')
					}));
				}.bind(this)).error(this.error.bind(this));
			} else {
				// change the title of the worksheet
				this.model.save({'name': this.buildWorksheetTitle()}, {patch:true}).success(function (response) {
					WorkbookCollection.updateWorksheet(response.data.worksheet);
				});

				this.model.analyze(analyzeObj);
			}
		},

		/**
		 * Reset the view
		 */
		reset: function () {
			// reset the query
			this.query = [];

			// reset the analysis type
			this.$el.find('select.chart-types').val('breakdown');
			this.$el.find('select.chart-types').trigger("chosen:updated");
			this.chartType = 'breakdown';

			// rerender
			this.subRender();
		},

		/**
		 * Build Worksheet Title
		 *
		 * Utility function to help build a title for a worksheet
		 */
		buildWorksheetTitle: function () {
			var title = [];

			if (this.query[0] === 'time') {
				return 'Time by ' + this.query[1];
			}

			if (this.chartType === 'sample') {
				return 'Sample ' + this.workbook.attributes.recording_id;
			}

			this.query.forEach(function (target) {
				_.flatten(this.dimensions.get('groups').map(function (di) {
					return di.items;
				})).forEach(function (dimension) {
					if (dimension.target === target) {
						title.push(dimension.label);
					}
				});
			}.bind(this));

			return title.join(' by ');
		},


		/**
		 * Get Info
		 *
		 * From a target get the complete object out of the dimensions object
		 *
		 * @param  {string} target
		 * @return {object}
		 */
		getInfo: function (target) {
			var r = false;
			this.dimensions.get('groups').forEach(function (group) {
				group.items.forEach(function (dimension) {
					if (dimension.target === target) {
						r = dimension;
					}
				});
			});
			return r;
		},

		/**
		 * Error
		 *
		 * Generic error handler
		 *
		 * @todo This will probably require a bit more work since I am not
		 * happy with the way we are currently handling error.
		 *
		 * @param  {jXHR} error Response from the error callback
		 */
		error: function (error) {

			this.$el.find('.submit-button').attr('data-spinner', '');

			if (400 === error.status) {

				var message = '';

				if (error.responseJSON.meta.error) {
					message = error.responseJSON.meta.error;
				} else {
					Object.keys(error.responseJSON.meta).forEach(function (key) {
						message = key + ': ' + error.responseJSON.meta[key];
					});
				}

                Buzzkill.notice(message);
            } else {
                ErrorFormatter.format(error);
            }
		}
	});

	return CreateWorksheetView;
});