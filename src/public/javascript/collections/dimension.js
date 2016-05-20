define(['jquery', 'underscore'],
function($, _) {
    'use strict';

    var instances = {};

    /**
     * Dimension collection for the given worksheet.
     *
     * @param {Number} projectId
     * @param {Number} worksheetId
     */
    var DimensionCollection = function(projectId, worksheetId) {
        this.endpoint = '/api/project/' + projectId + '/worksheet/' + worksheetId + '/dimensions';
        this.collection = [];
    };

    /**
     * Get the dimension collection.
     *
     * @return {Promise} Returns the current collection (if there is one)
     *                   or fetches it from the server
     */
    DimensionCollection.prototype.get = function() {
        return new Promise(function(resolve, reject) {
            if (this.collection.length > 0) {
                resolve(this.collection);
            } else {
                $.ajax(this.endpoint, {
                    type: 'GET',
                    contentType: 'application/json'
                }).done(function(response) {
                    var dimensionCollection = this.merge(response.data.groups).get();

                    resolve(dimensionCollection);
                }.bind(this)).fail(function(error) {
                    reject(new Error('! [Dimension Collection] ' + error.status + ': ' + error.statusText));
                });
            }
        }.bind(this));
    };

    /**
     * Gets only targets from the fetched dimensions.
     *
     * @return {Promise} Returns an array.
     */
    DimensionCollection.prototype.getTargets = function() {
        return this.get().then(function(dimensions) {
            var targets = [];
            _.each(_.pluck(dimensions, 'items'), function(items) {
                _.each(items, function(item) {
                    targets.push(item.target);
                });
            });
            return targets;
        });
    };

    /**
     * Add a dimension to our collection
     *
     * @param  {Object} dimension A dimension object
     * @return {Object}           Dimension collection instance
     */
    DimensionCollection.prototype.add = function(dimension) {
        this.collection.push(dimension);

        return this;
    };

    /**
     * Merge an array of dimensions with our current dimension collection
     *
     * @param  {Array}  dimensions An array of dimension objects
     * @return {Object}            Dimension collection instance
     */
    DimensionCollection.prototype.merge = function(dimensions) {
        this.collection = this.collection.concat(dimensions);

        return this;
    };

    return {
        getInstance: function(projectId, worksheetId) {
            var key = projectId + '-' + worksheetId;
            if (!instances[key]) {
                instances[key] = new DimensionCollection(projectId, worksheetId);
            }

            return instances[key];
        }
    }
});
