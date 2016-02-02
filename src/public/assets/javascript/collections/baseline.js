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
        this.collection = [];
    };

    /**
     * Get the Baseline collection.
     *
     * @return {Promise} Returns the current collection (if there is one)
     *                   or fetches it from the server
     */
    BaselineCollection.prototype.get = function() {
        return new Promise(function(resolve, reject) {
            if (this.collection.length > 0) {
                resolve(this.collection);
            } else {
                $.ajax(this.endpoint, {
                    type: 'GET',
                    contentType: 'application/json'
                }).done(function(response) {
                    var baselineCollection = this.merge(response.data).get();

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
    BaselineCollection.prototype.getById = function(id) {
        // cast to string as all ids in the collections are strings
        id = _.isNumber(id) ? id.toString() : id;
        return this.get().then(function(collection) {
            return _.find(collection, {id: id});
        });
    };

    /**
     * Add a baseline to our collection
     *
     * @param  {Object} baseline  A baseline object
     * @return {Object}           Baseline collection instance
     */
    BaselineCollection.prototype.add = function(baseline) {
        this.collection.push(baseline);

        return this;
    };

    /**
     * Merge an array of baselines with our current baseline collection
     *
     * @param  {Array}  baselines  An array of baseline objects
     * @return {Object}            Baseline collection instance
     */
    BaselineCollection.prototype.merge = function(baselines) {
        this.collection = this.collection.concat(baselines);

        return this;
    };

    instance = new BaselineCollection();

    return instance;
});
