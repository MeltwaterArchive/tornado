define(['jquery', 'underscore', 'promise', 'collections/workbook'],
function($, _, Promise, WorkbookCollection) {
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Dummy Workbook Locker service.
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
        return new Promise(function(res, rej) {
            res();
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

        return new Promise(function(res, rej) {
            res();
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
        return Promise.reject();
    };

    /**
     * Stops Workbook TTL action recurrence and reset Workbook's Locker data
     *
     * @param {Workbook} workbook
     *
     * @returns void
     */
    WorkbookLocker.prototype.stopTtlReset = function(workbook) {
        return;
    };

    /**
     * Extends Workbook object of Locker data
     *
     * @param {Workbook}
     *
     * @returns void
     */
    WorkbookLocker.prototype.addLockerData = function(workbook) {
        return;
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
