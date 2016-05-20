define([
    'jquery', 
    'router', 
    'promise', 
    'services/analyzer', 
    'loader', 
    'buzzkill', 
    'collections/dimension', 
    'collections/workbook', 
    'models/WorksheetModel',
    'models/WorkbookModel',
    'views/worksheet/worksheet', 
    'views/dimensions/dimensions', 
    'views/sidebar/DimensionsSidebarView'
], function(
    $, 
    router, 
    Promise, 
    analyzer, 
    Loader, 
    Buzzkill, 
    DimensionCollection, 
    WorkbookCollection,
    WorksheetModel,
    WorkbookModel,
    WorksheetView, 
    DimensionsView, 
    DimensionsSidebarView
) {
    'use strict';

    /**
     * Dimensions controller constructor
     *
     * @param {Number} projectId   Project ID
     * @param {Number} worksheetId Worksheet ID
     */
    var DimensionController = function(projectId, worksheetId, workbookId) {
        this.projectId = projectId;
        this.worksheetId = worksheetId;
        this.workbookId = workbookId;

        this.initialize();
    };

    DimensionController.prototype.initialize = function() {
        this.worksheet = WorkbookCollection.getWorksheetById(this.worksheetId);
        this.dimensionsCollection = DimensionCollection.getInstance(this.projectId, this.worksheetId);

        this.dimensionsCollection
            .get()
            .then(function(dimensions) {
                this.dimensions = dimensions;

                this.renderSubviews();
            }.bind(this));
    };

    DimensionController.prototype.submitDimensions = function(dimensions) {
        this.worksheet.dimensions = dimensions;

        var onSuccess = function() {
            router.navigateTo('/projects/' + this.projectId + '/worksheet/' + this.worksheetId);
        }.bind(this);

        var onError = function(error) {
            Loader.unload($('[data-dimension-build-button]'));
        };

        analyzer
            .analyze(this.worksheet)
            .then(onSuccess, onError);
    };

    /**
     * Initialize and render the subviews (Dimensions and Dimensions sidebar)
     */
    DimensionController.prototype.renderSubviews = function() {
        /**
         * This is a complete hash .....
         *
         * I want to create the structure i have in the end but without 
         * rewritting all the code. Therefore we create a dumb workbook and
         * set it as a parameter on the worksheet.
         *
         * @todo improve this
         */
        var wsm = new WorksheetModel(this.worksheet);
        var wbm = new WorkbookModel(WorkbookCollection.getModel(this.workbookId));
        wsm.set('workbook', wbm);
        // hydrate the dimensions
        wsm.hydrateDimensions(function (err, dimensions) {
            // get the dimensions
            var dsv = new DimensionsSidebarView({
                model: wsm
            });
            dsv.render();

             // use the old dimension view
            var dimensionsView = new DimensionsView({
                selectedDimensions: wsm.get('dimensions'),
                worksheet: this.worksheet,
                projectId: this.projectId
            });
            dimensionsView.onSubmitDimensions(this.submitDimensions.bind(this));
            dimensionsView.render();
        }.bind(this));
    };

    return DimensionController;
});
