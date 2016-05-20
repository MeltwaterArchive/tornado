define([
	'underscore',
	'backbone',
	'collections/WorksheetCollection',
	'models/DimensionModel'
], function (
	_,
	Backbone, 
	WorksheetCollection,
	DimensionModel
) {


	var WorkbookModel = Backbone.Model.extend({

		defaults: {
			worksheets: new WorksheetCollection()
		},

		initialize: function (obj) {

			if (!obj) {
				return;
			}

			var worksheets = new WorksheetCollection();
			// clone over the obj
			this.attributes = _.clone(obj);
			// are we trying to create an object with worksheets
			if (this.attributes && this.attributes.worksheets) {
				this.attributes.worksheets.forEach(function (worksheet) {
					var w = worksheets.add(worksheet);
					// tell the worksheet about it's parent
					w.set('workbook', this);
					// tell it about the project
					w.set('project_id', this.get('project_id'));
				}.bind(this));
				// set the worksheets to a collection
				this.attributes.worksheets = worksheets;
			}
		},

		getDimensions: function (callback) {

			// if we have the dimensions return them
			if (this.get('dimensions')) {
				return callback(null, this.get('dimensions'));
			}

			var dm = new DimensionModel({
				'project_id': this.get('project_id'),
				'workbook_id': this.get('id')
			});

			dm.fetch()
				.success(function () {
					this.set('dimensions', dm);
					callback(null, this.get('dimensions'));
				}.bind(this))
				.fail(callback);
			
		},

		getProject: function () {

		}

	});

	return WorkbookModel;
});