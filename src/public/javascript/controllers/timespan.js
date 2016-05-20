define(['jquery', 'router', 'spinner', 'loader', 'buzzkill', 'controllers/worksheet', 'collections/workbook', 'services/analyzer', 'views/timespan/timespan-sidebar'],
function($, router, Spinner, Loader, Buzzkill, WorksheetController, WorkbookCollection, analyzer, TimespanSidebarView) {
    'use strict';

    /**
     * Timespan controller constructor
     */
    var TimespanController = function(projectId, worksheetId) {
        this.projectId = projectId;
        this.worksheetId = worksheetId;

        this.initialize();
    };

    TimespanController.prototype.initialize = function() {
        this.worksheet = WorkbookCollection.getWorksheetById(this.worksheetId);
        this.renderTimespan();
    };

    TimespanController.prototype.submitTimespan = function(span, interval) {
        var _this = this;

        var onSuccess = function(charts) {
            router.navigateTo('/projects/' + _this.projectId + '/worksheet/' + _this.worksheetId);
        }.bind(this);

        var onError = function(error) {
            Loader.unload($('[data-timespan-apply-button]'));
            Buzzkill.alert($('[data-tornado-view="page-content"]'), error.statusText);
        }.bind(this);

        this.worksheet.span = span;
        this.worksheet.interval = interval;

        analyzer
            .analyze(this.worksheet)
            .then(onSuccess, onError);
    };

    /**
     * Initialize and render the subviews
     */
    TimespanController.prototype.renderTimespan = function() {
        var timespanSidebarView = new TimespanSidebarView({
            worksheet: this.worksheet
        });

        timespanSidebarView.onSubmitTimespan(this.submitTimespan.bind(this));
        timespanSidebarView.render();
        WorksheetController.renderWorksheet();
    };

    return TimespanController;
});
