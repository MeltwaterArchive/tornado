define([
	'config',
	'backbone'
], function (
	config,
	Backbone
) {
	/**
	 * Dimension Model
	 *
	 * This model contains all the information for the dimensions
	 *
	 * @todo prehaps this would work better as a chainable model, where each
	 * return of a filter method returns a new instance? However how do we then
	 * get back to the original model?
	 */
	var DimensionModel = Backbone.Model.extend({

		/**
		 * URL
		 *
		 * Override the default backbone URL to use the project id and workbook
		 * id in order to fetch the list of dimensions
		 *
		 * @overrides Backbone.Model.url
		 *
		 * @return {string} The URL
		 */
		url: function () {
			return '/api/project/' + this.get('project_id') + '/workbook/' +
			this.get('workbook_id') + '/dimensions';
		},

		/**
		 * Parse
		 *
		 * When we recieve the data from the services we need to make sure the
		 * JSON object is mapped correctly to this model
		 *
		 * @overrides Backbone.Model.parse
		 *
		 * @param  {object} res The server response
		 * @return {object}
		 */
		parse: function (res) {

			var obj = {};
			obj.groups = res.data.groups;
			obj.dimensions_count = res.meta.dimensions_count;
			obj.groups_count = res.meta.groups_count;

			return obj;
		},

		/**
		 * Get Dimension
		 *
		 * Fetch a dimension by it's target
		 *
		 * @param  {string} target
		 * @return {object}
		 */
		getDimension: function (target) {
			var di = false;
			this._flatten().forEach(function (dimension) {
				if (target === dimension.target) {
					di = dimension;
				}
			});
			return di;
		},

		/**
		 * Add Dimension
		 *
		 * Add a dimension by passing through an object containing at least
		 * a target and a group (capitalised first letter).
		 * 
		 * @param {object} dimension
		 */
		addDimension: function (dimension) {
			var dimensions = this._flatten();
			dimensions.push(dimension);

			this.set('groups', this._unflatten(dimensions).get('groups'));
		},

		/**
		 * Remove Dimension
		 *
		 * Remove a dimension from the current model
		 *
		 * @param  {string} target
		 * @return {DimensionModel}	Current Model
		 */
		removeDimension: function (target) {
			var dimensions = this._flatten().filter(function (d) {
				if (d.target !== target) {
					return true;
				}
			});
			this.set('groups', this._unflatten(dimensions).get('groups'));
			return this;
		},

		/**
		 * Filter Cardinality
		 *
		 * Filter the dimensions by the cardinality. This will remove all the
		 * dimensions which are over the supplied cardinality or the config
		 * variable for high cardinality (currently 30).
		 *
		 * @param  {int} cardinality
		 * @param  {boolean} emptyGroups Allow empty groups to be returned
		 * @return {DimensionModel}
		 */
		filterCardinality: function (cardinality, emptyGroups) {

			var dimensions = this._flatten().filter(function (di) {
				return !((!di.cardinality && di.cardinality !== 0) ||
					di.cardinality > (cardinality || config.highCardinality));
			});

			if (emptyGroups) {
				return this.hydrateGroups(dimensions);
			}

			return this._unflatten(dimensions);
		},

		/**
		 * Filter Allowability
		 *
		 * Filter the dimensions using the Natural Language Target Matrix which
		 * has been hand crafted by Jay to show the possible combinations of
		 * dimensions and which are compatible with the other ones.
		 *
		 * This require knowledge of the previous dimensions.
		 *
		 * @param  {array} query The other dimensions
		 * @param  {boolean} emptyGroups Allow empty groups to be returned
		 * @return {DimensionModel}
		 */
		filterAllowability: function (query, emptyGroups) {

			var highCardinality = false,
				dimensions = this._flatten();

			if (!query || query.length === 0) {
				return this;
			}

			// hydrate all the queries, current it's just an array of targets
			query = query.map(function (q) {
				var rtn = q;
				dimensions.forEach(function (target) {
					if (target.target === q) {
						rtn = target;
					}
				});
				return rtn;
			});

			query.forEach(function (q) {
				if (!q.cardinality || q.cardinality > config.highCardinality) {
					highCardinality = true;
				}
			});

			// do we have any other high cardinality filters
			if (highCardinality) {
			dimensions = dimensions.filter(function (di) {
				return !((!di.cardinality && di.cardinality !== 0) ||
				di.cardinality > config.highCardinality);
			});
		}

			if (emptyGroups) {
				return this.hydrateGroups(dimensions);
			}

			return this._unflatten(dimensions);
		},

		/**
		 * Label Filter
		 *
		 * Filter the dimensions by the labels. This will take a string and
		 * return a new dimension object with the non matching dimensions
		 * removed.
		 *
		 * If you would like to return empty groups then pass in true
		 *
		 * This doesn't alter the contents of the dimension model and just
		 * returns a new group obj.
		 *
		 * @param  {string} label        The search string
		 * @param  {boolean} emptyGroups Allow empty groups to be returned
		 * @return {DimensionModel}
		 */
		filterLabel: function (label, emptyGroups) {

			var dimensions = this._flatten().filter(function (di) {
				return di.label.toLowerCase().indexOf(label)
					!== -1 ? true : false;
			});

			if (emptyGroups) {
				return this.hydrateGroups(dimensions);
			}

			return this._unflatten(dimensions);
		},

		/**
		 * Hydrate Groups
		 *
		 * When we are manipulating the dimensions we often remove all the
		 * dimensions in a given group and thus remove the group. This function
		 * will climb back up the DimensionModel parents until the top and
		 * reinsert all the groups as empty groups
		 *
		 * @param  {object} dimensions flattened dimension object
		 * @return {DimensionModel}
		 */
		hydrateGroups: function (dimensions) {

			dimensions = dimensions || this._flatten();

			if (this.get('parent')) {
				return this.get('parent').hydrateGroups(dimensions);
			}

			this.get('groups').forEach(function (group) {
				var num = dimensions.filter(function (d) {
					return d.name === group.name;
				}).length;

				if (num === 0) {
					dimensions.push({
						group: group.name
					});
				}
			});

			return this._unflatten(dimensions);

		},

		/**
		 * Flatten
		 *
		 * Take the nested dimension object and return it flattened with the
		 * group being a parameter of the object. While this may not be as
		 * efficent it's a lot neater and more understandable than nested
		 * forEach loops
		 *
		 * @private
		 *
		 * @param  {array} groups An Array of groups
		 * @return {array}
		 */
		_flatten: function (groups) {
			var dimensions = [];
			groups = groups || _.clone(this.get('groups'));
			// flatten the structure, this will be quicker in the long term
			groups.forEach(function (group) {
				group.items.forEach(function (dimension) {
					dimension.group = group.name;
					dimensions.push(dimension);
				});
			});

			return dimensions;
		},

		/**
		 * Unflatten
		 *
		 * Take the flattened object and restore is back to the original format
		 * that was passed into _flatten.
		 *
		 * Warning be sure to prefix this with a { groups: [] } when needed
		 *
		 * @private
		 *
		 * @param  {array} flattened
		 * @return {DimensionModel}
		 */
		_unflatten: function (flattened) {
			var ret = [];
			flattened.forEach(function (di) {
				var i = ret.map(function (group) {
					return group.name;
				}).indexOf(di.group);

				if (i === -1) {
					ret.push({
						'name': di.group,
						'items': Object.keys(di).length > 1 ? [di] : []
					});
				} else if (Object.keys(di).length > 1) {
					ret[i].items.push(di);
				}
			});

			return new DimensionModel({
				'groups': ret,
				'parent': this
			});
		}
	});

	return DimensionModel;
});