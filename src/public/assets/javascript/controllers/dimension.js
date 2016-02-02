define(['jquery', 'router', 'promise', 'services/analyzer', 'loader', 'buzzkill', 'collections/dimension', 'collections/workbook', 'views/worksheet/worksheet', 'views/dimensions/dimensions', 'views/dimensions/dimensions-sidebar'],
function($, router, Promise, analyzer, Loader, Buzzkill, DimensionCollection, WorkbookCollection, WorksheetView, DimensionsView, DimensionsSidebarView) {
    'use strict';

    /**
     * Dimensions controller constructor
     *
     * @param {Number} projectId   Project ID
     * @param {Number} worksheetId Worksheet ID
     */
    var DimensionController = function(projectId, worksheetId) {
        this.projectId = projectId;
        this.worksheetId = worksheetId;

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
        var dimensionsView = new DimensionsView({
            selectedDimensions: this.worksheet.dimensions,
            worksheet: this.worksheet,
            projectId: this.projectId
        });

        var dimensionsSidebarView = new DimensionsSidebarView({
            dimensionsGroups: this.dimensions
        });

        dimensionsView.onSubmitDimensions(this.submitDimensions.bind(this));

        // render the sidebar first as the main view relies on dimensions list there
        dimensionsSidebarView.render();
        dimensionsView.render();
    };

    return DimensionController;
});
