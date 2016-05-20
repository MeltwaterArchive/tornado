define([
	'backbone',
	'models/ChartModel'
], function (Backbone, ChartModel) {

	'use strict';

	var ChartCollection = Backbone.Collection.extend({
		model: ChartModel
	});

	return ChartCollection;

});