define([
    'jquery', 
    'router', 
    'loader', 
    'spinner', 
    'controllers/worksheet', 
    'collections/dimension', 
    'collections/workbook', 
    'collections/recording', 
    'services/analyzer', 
    'models/WorksheetModel',
    'views/filters/filters-sidebar'
], function(
    $, 
    router, 
    Loader, 
    Spinner, 
    WorksheetController, 
    DimensionCollection, 
    WorkbookCollection, 
    RecordingCollection, 
    analyzer, 
    WorksheetModel,
    FiltersSidebarView
) {
    'use strict';

    /**
     * Filters controller constructor
     */
    var FiltersController = function(projectId, worksheetId) {
        this.projectId = projectId;
        this.worksheetId = worksheetId;

        this.initialize();
    };

    FiltersController.prototype.initialize = function() {
        this.worksheet = WorkbookCollection.getWorksheetById(this.worksheetId);
        this.workbook = WorkbookCollection.getModel(this.worksheet.workbook_id);

        Promise.all([
            DimensionCollection.getInstance(this.projectId, this.worksheetId).getTargets(),
            RecordingCollection.getById(this.projectId, this.workbook.recording_id)
        ]).then(function(results) {
            this.targets = results[0];
            this.recording = results[1];

            this.renderFilters();
        }.bind(this));
    };

    FiltersController.prototype.submitFilters = function(filters) {
        var onSuccess = function () {
            router.navigateTo('/projects/' + this.projectId + '/worksheet/' + this.worksheetId);
        }.bind(this);

        var onError = function() {

            var worksheet = new WorksheetModel({
                'project_id': this.projectId,
                'id': this.worksheetId
            });

            worksheet.fetch({
                'success': function () {
                    this.worksheet = worksheet.attributes;
                    // make sure the collection is updated
                    WorkbookCollection.updateWorksheet(this.worksheet);
                    this.renderFilters();
                    Spinner.stop($('[data-filters-apply-button]'));
                    Loader.unload($('[data-filters-apply-button]'));
                }.bind(this)
            });
            
        }.bind(this);

        this.worksheet.filters = filters;
        this.worksheet.start = filters.start;
        this.worksheet.end = filters.end;

        if (this.model && this.model.get('chart_type') == 'sample') {
            this.model.set({
                'filters': filters,
                'start': filters.start,
                'end': filters.end,
                'type': 'sample'
            });
            return this.model.analyze().then(onSuccess, onError);
        }
        analyzer
            .analyze(this.worksheet)
            .then(onSuccess, onError);

    };

    /**
     * Initialize and render the subviews (Filters form)
     */
    FiltersController.prototype.renderFilters = function() {
        var _this = this;
        var filtersSidebarView = new FiltersSidebarView({
            worksheet: this.worksheet,
            recording: this.recording,
            targets: this.targets
        });

        filtersSidebarView.onSubmitFilters(_this.submitFilters.bind(_this));
        filtersSidebarView.render();
        WorksheetController.data.worksheet = this.worksheet;
        WorksheetController.renderWorksheet();
    };

    return FiltersController;
});
