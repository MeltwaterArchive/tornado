define([
	'backbone',
	'models/WorksheetModel'
], function (Backbone, WorksheetModel) {
	
	"use strict";

	var WorksheetCollection = Backbone.Collection.extend({
		model: WorksheetModel,
		comparator: 'order'
	});

	return WorksheetCollection;
});