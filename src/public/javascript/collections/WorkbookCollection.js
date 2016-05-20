define([
	'backbone',
	'collections/WorksheetCollection',
	'models/WorkbookModel'
], function (Backbone, WorksheetCollection, WorkbookModel) {

	'use strict';

	var WorkbookCollection = Backbone.Collection.extend({
		model: WorkbookModel
	});

	return WorkbookCollection;

});