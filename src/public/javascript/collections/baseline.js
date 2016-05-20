define(['jquery', 'underscore'],
function($, _) {
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Baseline collection
     *
     * @singleton
     */
    var BaselineCollection = function() {
        this.endpoint = '/datasets';
        this.collection = {};
    };

    /**
     * Get the Baseline collection.
     *
     * @return {Promise} Returns the current collection (if there is one)
     *                   or fetches it from the server
     */
    BaselineCollection.prototype.get = function(workbookId) {
        return new Promise(function(resolve, reject) {
            if (workbookId in this.collection) {
                resolve(this.collection[workbookId]);
            } else {
                $.ajax(this.endpoint + '/' + workbookId, {
                    type: 'GET',
                    contentType: 'application/json'
                }).done(function(response) {
                    var baselineCollection = this.merge(response.data, workbookId).get(workbookId);

                    resolve(baselineCollection);
                }.bind(this)).fail(function(error) {
                    reject(new Error('! [Baseline Collection] ' + error.status + ': ' + error.statusText));
                });
            }
        }.bind(this));
    };

    /**
     * Get a Baseline by ID.
     *
     * @param  {Number} id The ID.
     *
     * @return {Promise}
     */
    BaselineCollection.prototype.getById = function(id, workbookId) {
        // cast to string as all ids in the collections are strings
        id = _.isNumber(id) ? id.toString() : id;
        return this.get(workbookId).then(function(collection) {
            return _.find(collection, {id: id});
        });
    };

    /**
     * Add a baseline to our collection
     *
     * @param  {Object} baseline  A baseline object
     * @return {Object}           Baseline collection instance
     */
    BaselineCollection.prototype.add = function(baseline, workbookId) {
        this.collection[workbookId].push(baseline);

        return this;
    };

    /**
     * Merge an array of baselines with our current baseline collection
     *
     * @param  {Array}  baselines  An array of baseline objects
     * @return {Object}            Baseline collection instance
     */
    BaselineCollection.prototype.merge = function(baselines, workbookId) {
        if (!(workbookId in this.collection)) {
            this.collection[workbookId] = [];
        }
        this.collection[workbookId] = this.collection[workbookId].concat(baselines);

        return this;
    };

    instance = new BaselineCollection();

    return instance;
});
