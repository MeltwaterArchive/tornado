define(['jquery', 'underscore', 'promise', 'collections/workbook'],
function($, _, Promise, WorkbookCollection) {
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Workbook Locker service.
     *
     * @param {WorkbookCollection} workbookCollection
     * @param {Object} options
     *
     * @constructor
     */
    var WorkbookLocker = function(workbookCollection, options) {
        this.workbookCollection = workbookCollection;
        this.options = _.extend({
            intervalTime: 60 * 1000,
            errorLimit: 5 // errors fix attempts
        }, options || {});
    };

    /**
     * Locks Workbook and extends its data of Locker object.
     *
     * @param {Number} projectId
     * @param {Number} workbookId
     *
     * @returns {Promise}
     */
    WorkbookLocker.prototype.lock = function(projectId, workbookId) {
        var workbook = this.workbookCollection.getModel(workbookId);

        // remove interval which may existing due to tabs switching
        this.stopTtlReset(workbook);

        return new Promise(function(res, rej) {
            $.ajax('/api/project/' + projectId + '/workbook/' + workbook.id + '/lock', {
                type: 'POST',
                contentType: 'application/json'
            }).then(function(data) {
                    this.addLockerData(workbook);
                    workbook.locker.isLocked = true;

                    res(data);
                }.bind(this)
            ).fail(function(err) {
                    this.addLockerData(workbook);
                    workbook.locker.isLocked = false;

                    rej(err);
                }.bind(this))
        }.bind(this));
    };

    /**
     * Unlocks Workbook if locked
     *
     * @param {Number} projectId
     * @param {Number} workbookId
     *
     * @returns {Promise}
     */
    WorkbookLocker.prototype.unlock = function(projectId, workbookId) {
        var workbook = this.workbookCollection.getModel(workbookId);

        // remove interval which may existing due to tabs switching
        this.stopTtlReset(workbook);

        return new Promise(function(res, rej) {
            $.ajax('/api/project/' + projectId + '/workbook/' + workbook.id + '/unlock', {
                type: 'DELETE',
                contentType: 'application/json'
            }).then(function(data) {
                    res(data);
                }.bind(this)
            ).fail(function(err) {
                    rej(err);
                }.bind(this))
        }.bind(this));
    };

    /**
     * Resets Workbook's TTL reset repetition when repetition counter reaches 0.
     *
     * Counts server errors and checks if it numbers is equal the error top limit value, if so,
     * rejects Promise.
     *
     * @param {Number} projectId
     * @param {Number} workbookId
     *
     * @returns {Promise}
     */
    WorkbookLocker.prototype.ttlReset = function(projectId, workbookId) {
        var workbook = this.workbookCollection.getModel(workbookId);
        var _this = this;

        if (!workbook.locker.isLocked) {
            return Promise.reject({
                type: 'hidden',
                message: 'Workbook is not locked to reset its TTL.'
            });
        }

        var ttlReset = function(res, rej) {
            return function() {
                $.ajax('/api/project/' + projectId + '/workbook/' + workbook.id + '/lock/ttl-reset', {
                    type: 'PUT',
                    contentType: 'application/json'
                }).then(function(response) {
                    // if remaining_counter data is missing in server response, that means something went wrong
                    if (!_.has(response, 'meta') || !_.has(response.meta, 'remaining_counter')) {
                        _this.stopTtlReset(workbook);

                        return rej({
                            type: 'notify',
                            message: 'Internal server error.'
                        });
                    }

                    // if TTL reset action exceed a top repetition limit, stops recurrence
                    if (0 === response.meta.remaining_counter) {
                        _this.stopTtlReset(workbook);

                        return res(
                            'You\'ve been inactive for long time. Workbook is going to be unlocked in '
                            + response.meta.ttl + ' seconds.'
                        );
                    }
                }, function(err) {
                    // if errors reaches the error attempts limit, clear interval and reject response
                    if (_this.options.errorLimit === workbook.locker.errorCounter) {
                        _this.stopTtlReset(workbook);

                        return rej(err);
                    }

                    // increment error attempts counter
                    workbook.locker.errorCounter++;
                    console.log('locker error: ', workbook.locker.errorCounter);
                })
            }
        };

        return new Promise(function(res, rej) {
            workbook.locker.interval = setInterval(ttlReset(res, rej), _this.options.intervalTime);
        });
    };

    /**
     * Stops Workbook TTL action recurrence and reset Workbook's Locker data
     *
     * @param {Workbook} workbook
     *
     * @returns void
     */
    WorkbookLocker.prototype.stopTtlReset = function(workbook) {
        if (!workbook.locker || !workbook.locker.interval) {
            return;
        }

        clearInterval(workbook.locker.interval);
        this.clearLockerData(workbook);
    };

    /**
     * Extends Workbook object of Locker data
     *
     * @param {Workbook}
     *
     * @returns void
     */
    WorkbookLocker.prototype.addLockerData = function(workbook) {
        if (_.has(workbook, 'locker')) {
            return;
        }

        this.clearLockerData(workbook);
    };

    /**
     * Clears Workbook's Locker data
     *
     * @param {Workbook}
     *
     * @returns void
     */
    WorkbookLocker.prototype.clearLockerData = function(workbook) {
        workbook.locker = {
            isLocked: false,
            interval: null,
            errorCounter: 0
        };
    };

    instance = new WorkbookLocker(WorkbookCollection);

    return instance;
});
