 define([
	'underscore',
	'backbone'
], function (
	_,
	Backbone
) {

	'use strict';

	var EventModel = {};
	_.extend(EventModel, Backbone.Events);

	return EventModel;
});