define(['router', 'promise', 'loader', 'buzzkill', 'controllers/worksheet', 'collections/dimension', 'collections/workbook', 'services/analyzer', 'views/comparison/comparison-sidebar'],
function(router, Promise, Loader, Buzzkill, WorksheetController, DimensionCollection, WorkbookCollection, analyzer, ComparisonSidebarView) {
    'use strict';

    /**
     * Comparison controller constructor
     */
    var ComparisonController = function(projectId, worksheetId) {
        this.projectId = projectId;
        this.worksheetId = worksheetId;
        this.recordings = [];
        this.datasets = [];

        this.initialize();
    };

    ComparisonController.prototype.initialize = function() {
        var _this = this;

        this.worksheet = WorkbookCollection.getWorksheetById(this.worksheetId);

        Promise.all([
            this.getProjectRecordings(),
            this.getDataSets(),
            this.getTargets()
        ]).then(function() {
            _this.renderSubviews();
        });
    };

    ComparisonController.prototype.getProjectRecordings = function() {
        var _this = this;

        return new Promise(function(resolve, reject) {
            $.get('/api/project/' + _this.projectId + '/recordings')
                .done(function(response) {
                    _this.recordings = response.data;

                    resolve(_this.recordings);
                }).fail(function(error) {
                    reject(error);
                });
        });
    };

    ComparisonController.prototype.getDataSets = function() {
        var _this = this;

        return new Promise(function(resolve, reject) {
            $.get('/datasets')
                .done(function(response) {
                    _this.datasets = response.data;

                    resolve(_this.datasets);
                }).fail(function(error) {
                    reject(error);
                });
        });
    };

    ComparisonController.prototype.getTargets = function() {
        DimensionCollection.getInstance(this.projectId, this.worksheetId)
            .getTargets()
            .then(function(targets) {
                this.targets = targets;
            }.bind(this));
    };

    ComparisonController.prototype.submitComparison = function(comparison, recordingId, datasetId, filters) {
        var _this = this;

        var onSuccess = function(charts) {
            router.navigateTo('/projects/' + _this.projectId + '/worksheet/' + _this.worksheetId);
        }.bind(this);

        var onError = function(error) {
            Loader.unload($('[data-comparison-apply-button]'));
            Buzzkill.alert($('[data-tornado-view="page-content"]'), error.statusText);
        }.bind(this);

        this.worksheet.comparison = comparison;
        this.worksheet.secondary_recording_id = recordingId;
        this.worksheet.secondary_recording_filters = filters;
        this.worksheet.baseline_dataset_id = datasetId;

        analyzer
            .analyze(this.worksheet)
            .then(onSuccess, onError);
    };

    /**
     * Initialize and render the subviews (Filters form)
     */
    ComparisonController.prototype.renderSubviews = function() {
        var _this = this;
        var comparisonSidebarView = new ComparisonSidebarView({
            analysis_type: this.worksheet.analysis_type,
            span: this.worksheet.span,
            interval: this.worksheet.interval,
            comparison: this.worksheet.comparison,
            disabled_recording: this.worksheet.recording_id,
            selected_recording: this.worksheet.secondary_recording_id,
            selected_dataset: this.worksheet.baseline_dataset_id,
            secondary_recording_filters: this.worksheet.secondary_recording_filters,
            recordings: this.recordings,
            datasets: this.datasets,
            projectId: this.projectId,
            worksheetId: this.worksheetId,
            targets: this.targets
        });

        comparisonSidebarView.onSubmitComparison(_this.submitComparison.bind(_this));
        comparisonSidebarView.render();
        WorksheetController.renderWorksheet();
    };

    return ComparisonController;
});
