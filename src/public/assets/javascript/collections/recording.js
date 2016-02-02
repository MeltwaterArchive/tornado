define(['jquery', 'underscore'],
function($, _) {
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Recording collection
     *
     * @singleton
     */
    var RecordingCollection = function() {
        this.collection = [];
    };

    /**
     * Get the Recording collection.
     *
     * @param  {Number} projectId Owning project ID.
     *
     * @return {Promise} Returns the current collection (if there is one)
     *                   or fetches it from the server
     */
    RecordingCollection.prototype.get = function(projectId) {
        return new Promise(function(resolve, reject) {
            if (this.collection.length > 0) {
                resolve(this.collection);
            } else {
                var endpoint = '/api/project/' + projectId + '/recordings';

                $.ajax(endpoint, {
                    type: 'GET',
                    contentType: 'application/json'
                }).done(function(response) {
                    var recordingCollection = this.merge(response.data).get();

                    resolve(recordingCollection);
                }.bind(this)).fail(function(error) {
                    reject(new Error('! [Recording Collection] ' + error.status + ': ' + error.statusText));
                });
            }
        }.bind(this));
    };

    /**
     * Get a Baseline by ID.
     *
     * @param  {Number} projectId Owning project ID.
     * @param  {Number} id        Recording ID to look for.
     *
     * @return {Promise}
     */
    RecordingCollection.prototype.getById = function(projectId, id) {
        // cast to string as all ids in the collections are strings
        id = _.isNumber(id) ? id.toString() : id;
        return this.get(projectId).then(function(collection) {
            return _.find(collection, {id: id});
        });
    };

    /**
     * Add a recording to our collection
     *
     * @param  {Object} recording A recording object
     * @return {Object}           Recording collection instance
     */
    RecordingCollection.prototype.add = function(recording) {
        this.collection.push(recording);

        return this;
    };

    /**
     * Merge an array of recordings with our current recording collection
     *
     * @param  {Array}  recordings An array of recording objects
     * @return {Object}            Recording collection instance
     */
    RecordingCollection.prototype.merge = function(recordings) {
        this.collection = this.collection.concat(recordings);

        return this;
    };

    instance = new RecordingCollection();

    return instance;
});
