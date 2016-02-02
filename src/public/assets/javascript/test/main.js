define([
	'qunit',
	'test/services/storage/localstorage'
], function () {
	var arg = [].slice.call(arguments);
	// shift off the quint variable
	var qunit = arg.shift();
	// for each of the rest run the unit tests
	arg.forEach(function (test) {
		test();
	});
	// load & start qunit
	qunit.load();
	qunit.start();
});