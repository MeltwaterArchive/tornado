define([
	'backbone'
], function (Backbone) {

	var Menu = Backbone.Model.extend({
		projectId: null, 
		worksheetId: null,
		visible: false,
		controller: false,
		worksheet: null
	});

	return new Menu();
});