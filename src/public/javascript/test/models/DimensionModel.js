define([
	'models/DimensionModel',
	'text!test/resources/models/dimensions.json'
], function (DimensionModel, Dimensions) {


	Dimensions = JSON.parse(Dimensions);

	var obj = {};
	obj.groups = Dimensions.data.groups;
	obj.dimensions_count = Dimensions.meta.dimensions_count;
	obj.groups_count = Dimensions.meta.groups_count;

	// create a dummy model
	var dm = new DimensionModel(obj);

	var DimensionModelTest = function () {

		test('DimensionModel::getDimension', function () {
			var age = dm.getDimension('fb.author.age'),
				age2 = Dimensions.data.groups[0].items[0];

			equal(
				JSON.stringify(age), 
				JSON.stringify(age2),
				'Failed to fetch the Dimension'
			);
		});

		test('DimensionModel::removeDimension', function () {
			var cloneDm = new DimensionModel(obj);
			cloneDm.removeDimension('fb.author.age');
			notOk(cloneDm.getDimension('fb.author.age')); 
		});

		test('DimensionModel::filterCardinality', function () {
			var dimensions = dm.filterCardinality(0);
			var results = dimensions._flatten().filter(function (di) {
				return di.cardinality < 1;
			});
			equal(JSON.stringify(results), '[]', 'The filter list should return nothing');
		});

		test('DimensionModel::filterLabel', function () {
			var dimensions = dm.filterLabel('age');
			ok(dimensions._flatten().length === 12);
		});

		// this actually tests _flatten and _unflatten
		test('DimensionModel::_flatten', function () {

			var flattened = dm._flatten(),
				unflattened = dm._unflatten(flattened);

			equal(
				JSON.stringify(dm.get('groups')), 
				JSON.stringify(unflattened.get('groups'))
			);
		});
	};
	
	return DimensionModelTest;
});