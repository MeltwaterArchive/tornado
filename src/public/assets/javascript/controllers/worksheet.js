define([
    'jquery', 
    'underscore', 
    'promise', 
    'collections/workbook', 
    'collections/baseline', 
    'collections/recording', 
    'views/worksheet/worksheet', 
    'views/worksheet/no-workbooks', 
    'views/worksheet/no-worksheet', 
    'collections/chart', 
    'models/menu', 
    'views/global/page-title'],
function($, _, Promise, WorkbookCollection, DatasetCollection, 
    RecordingCollection, WorksheetView, NoWorkbooksView, NoWorksheetView, 
    ChartCollection, Menu, PageTitle) {

    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Worksheet controller constructor
     *
     * @param {Object} data Worksheet and Chart data
     */
    var WorksheetController = function() {
        this.data = {
            worksheet: {},
            charts: {},
            project: {},
            workbook: {},
            baselineDataset: null,
            secondaryRecording: null
        };
    };

    WorksheetController.prototype.getData = function(projectId, worksheetId) {
        var _this = this;
        var endpoint = '/api/project/' + projectId + '/worksheet/' + worksheetId;
        var ProjectController = null;

        return new Promise(function(resolve, reject) {
            require(['controllers/project'], function(controller) {
                ProjectController = controller;

                var worksheet = WorkbookCollection.getWorksheetById(worksheetId);

                if (_.isUndefined(worksheet) || _.isUndefined(worksheet.fetched)) {
                    $.get(endpoint)
                        .done(function(response) {
                            _this.data.worksheet = response.data.worksheet;
                            _this.data.charts = response.data.charts;

                            ChartCollection.merge(_this.data.charts);
                            worksheet.fetched = true;

                            resolve(_this);
                        })
                        .fail(function(error) {
                            reject(error);
                        });
                } else {
                    _this.data.worksheet = worksheet;
                    _this.data.charts = ChartCollection.getByWorksheetId(worksheet.id);

                    resolve(_this);
                }
            });

        }).then(function() {
            if (_this.data.worksheet.baseline_dataset_id) {
                return DatasetCollection.getById(_this.data.worksheet.baseline_dataset_id)
                    .then(function(dataset) {
                        _this.data.baselineDataset = dataset;
                    });
            }

            if (_this.data.worksheet.secondary_recording_id) {
                return RecordingCollection.getById(projectId, _this.data.worksheet.secondary_recording_id)
                    .then(function(recording) {
                        _this.data.secondaryRecording = recording;
                    });
            }

        }).then(function() {
            _this.data.workbook = WorkbookCollection.getModel(_this.data.worksheet.workbook_id);
            ProjectController.data.workbookId = _this.data.worksheet.workbook_id;

            ProjectController
                .renderProjectWorksheetList(_this.data.worksheet)
                .renderProjectWorkbookSidebar(ProjectController.data.workbookId);


            /////////
            /*Menu.update({
                project: ProjectController.data.project,
                worksheet: _this.data.worksheet
            });*/
            Menu.set({
                'worksheet': _this.data.worksheet
            });

            PageTitle.update({ workbookName: _this.data.workbook.name });

            ProjectController.removeLoadingIndicator();

            return _this;
        });
    };

    WorksheetController.prototype.renderNoWorkbooks = function() {
        this.view = new NoWorkbooksView();

        this.view.render();
    };

    WorksheetController.prototype.renderNoWorksheet = function() {
        this.view = new NoWorksheetView();

        this.view.render();
    };

    WorksheetController.prototype.renderWorksheet = function() {
        this.view = new WorksheetView(this.data);

        this.view.render();
    };

    instance = new WorksheetController();

    return instance;
});
