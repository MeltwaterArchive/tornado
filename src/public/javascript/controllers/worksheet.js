define([
    'jquery',
    'underscore',
    'promise',
    'collections/workbook',
    'collections/baseline',
    'collections/recording',
    'models/WorksheetModel',
    'views/worksheet/worksheet',
    'views/worksheet/no-workbooks',
    'views/worksheet/no-worksheet',
    'views/worksheet/WorksheetView',
    'collections/chart',
    'models/menu'
], function(
    $,
    _,
    Promise,
    WorkbookCollection,
    DatasetCollection,
    RecordingCollection,
    WorksheetModel,
    OldWorksheetView,
    NoWorkbooksView,
    NoWorksheetView,
    WorksheetView,
    ChartCollection,
    Menu
) {

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
            posts: {},
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
                            _this.data.posts = response.data.posts;

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

                    /**
                     * We are not modifying the post object here because the posts are 
                     * a new concept which only exists in the new worksheet model.
                     * See router.js::ensureWorksheet for this logic
                     */

                    resolve(_this);
                }
            });

        }).then(function() {
            if (_this.data.worksheet.baseline_dataset_id) {
                return DatasetCollection.getById(_this.data.worksheet.baseline_dataset_id, _this.data.worksheet.workbook_id)
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

            ProjectController.removeLoadingIndicator();

            return _this;
        });
    };

    WorksheetController.prototype.renderNoWorkbooks = function() {
        this.view = new NoWorkbooksView();
        this.view.render();
    };

    WorksheetController.prototype.renderNoWorksheet = function (projectId, workbookId) {

        var wsm = new WorksheetModel({
            'project_id': projectId,
            'workbook_id': workbookId
        });

        new WorksheetView({
            model: wsm
        }).render();
    };

    WorksheetController.prototype.renderWorksheet = function() {

        if (this.data.worksheet.analysis_type != 'sample') {
            this.view = new OldWorksheetView(this.data);
            return this.view.render();
        }

        if (this.worksheetView) {
            this.worksheetView.remove();
        }

        // Since we are migrating to proper backbone view, while all the functionality
        // is being migrated to the new one, we use the Old one to render, but we initialize
        // the new one to be able to access the functionality.
        this.worksheetView = new WorksheetView({model: this.newModel});
        this.worksheetView.render();
    };

    instance = new WorksheetController();

    return instance;
});
