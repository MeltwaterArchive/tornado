define([
	'services/storage/localstorage'
], function (localstorage) {

	var LocalStorageTest = function () {

		test('LocalStorage::create', function () {
			// create the item
			var testObj = {'test':'test'},
				fetchObj = false;

			localstorage.createItem('test', testObj);
			fetchObj = localstorage.getItem('test');
			
			// we have to compare stringified versions 
			equal(
				JSON.stringify(testObj), 
				JSON.stringify(fetchObj), 
				'The fetched should be the same as the created object'
			);
		});
	};
	
	return LocalStorageTest;
});