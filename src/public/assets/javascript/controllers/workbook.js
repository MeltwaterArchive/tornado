define(['jquery', 'underscore', 'promise', 'services/workbook/locker', 'collections/workbook', 'buzzkill', 'services/http/error-formatter'],
function($, _, Promise, WorkbookLocker, WorkbookCollection, Buzzkill, ErrorFormatter) {
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Workbook Controller
     *
     * @param {WorkbookLocker} workbookLocker
     * @param {WorkbookCollection} workbookCollection
     *
     * @constructor
     */
    var WorkbookController = function(workbookLocker, workbookCollection) {
        this.workbookLocker = workbookLocker;
        this.workbookCollection = workbookCollection;
        this.lockInterval = null;
        this.intervalTime = 60 * 1000;

        this.bindEvents();
    };

    /**
     * Registers Workbook controller listeners
     *
     * @private
     */
    WorkbookController.prototype.bindEvents = function() {
        // workbook created
        $('body').on('createworkbook.project', function(ev, data) {
            // if create workbook context is not an another workbook, than no need to unlock it
            if (!data.prevWorkbook) {
                return;
            }

            this.stopLockRecurrence();

            this.workbookLocker.unlock(data.prevWorkbook.project_id, data.prevWorkbook.id)
                .then(function(val) {
                    // no need to notify user, just log the response
                    console.log('! [WorkbookController: create workbook] Success:', val);
                }, function(err) {
                    // no need to notify user, just log the error
                    console.error('! [WorkbookController: create workbook] ' + err.status + ': ', err);
                });
        }.bind(this));

        // workbook deleted - no need to make an unlock request, workbook won't be accessible for user anymore
        $('body').on('deleteworkbook.project', function(ev, data) {
            this.stopLockRecurrence();
            this.workbookLocker.stopTtlReset(data.workbook);
        }.bind(this));

        // workbook switched
        $(document).on('switch.workbooksidebar', function(ev, data) {
            this.stopLockRecurrence();

            this.workbookLocker.unlock(data.prevWorkbook.project_id, data.prevWorkbook.id)
                .then(function(val) {
                    // no need to notify user, just log the response
                    console.log('! [WorkbookController: switch workbook] Success:', val);
                }, function(err) {
                    // no need to notify user, just log the error
                    console.error('! [WorkbookController: switch workbook] ' + err.status + ': ', err);
                });
        }.bind(this));

        // analyzer request
        $(document).on('analyzer.project', function(ev, data) {
            this.stopLockRecurrence();
            var workbook = this.workbookCollection.getModel(data.workbookId);

            // perform lock action to reset TTL counter
            this.lock(workbook.project_id, workbook.id);
        }.bind(this));
    };

    /**
     * Locks given Workbook and starts periodical Workbook's TTL reset action
     *
     * @param {Number} projectId
     * @param {Number} workbookId
     */
    WorkbookController.prototype.lock = function(projectId, workbookId) {
        this.workbookLocker.lock(projectId, workbookId)
            .then(function() {
                    // @todo removing old alert of i.e. user inactivity

                    // remove lock recurrence interval
                    this.stopLockRecurrence();

                    // start Workbook's TTL reset recurrence action. That ensures that workbook won't be blocked
                    // without any reasons for too long time, which could happen i.e by setting initial TTL value
                    // on the server to 30 min.
                    return this.workbookLocker.ttlReset(projectId, workbookId);
                }.bind(this)
            ).then(function(val) {
                // Info about exceeded max time of inactivity
                // @todo replace Buzkill alert by something more user friendly?

                Buzzkill.notice(val);

            }, function(err) {
                //user is not granted to lock the Workbook (which means that Workbook is already locked by another user
                if (403 === err.status) {
                    $(document).trigger('workbook.locked');
                    return this.startLockRecurrence(err, projectId, workbookId);
                }

                // server error or conflict, ensure interval is cleared
                this.stopLockRecurrence();

                if (_.has(err, 'type')) {
                    if ('notify' !== err.type) {
                        console.log(err.message);
                        return;
                    }

                    return Buzzkill.notice(err.message);
                }

                ErrorFormatter.format(err);
            }.bind(this));
    };

    /**
     * Starts Workbook lock action in recurrence mode and sets lock notification
     *
     * @param {Error} err
     * @param {Number} projectId
     * @param {Number} workbookId
     *
     * @returns void
     */
    WorkbookController.prototype.startLockRecurrence = function(err, projectId, workbookId) {
        if (this.lockInterval) {
            return;
        }

        // @todo - add lock notification
        //ErrorFormatter.format(err);
        Buzzkill.notice(err.responseJSON.meta.error, 'warning');

        this.lockInterval = setInterval(function() {
            this.lock(projectId, workbookId);
        }.bind(this), this.intervalTime);
    };

    /**
     * Stops Workbook locking recurrence action, if exists, and removes lock notification.
     *
     * @returns void
     */
    WorkbookController.prototype.stopLockRecurrence = function() {
        if (!this.lockInterval) {
            return;
        }

        clearInterval(this.lockInterval);
        // @todo remove workbook lock notification
        ErrorFormatter.clear();
    };

    instance = new WorkbookController(WorkbookLocker, WorkbookCollection);

    return instance;
});
