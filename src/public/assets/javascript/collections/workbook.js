define(['jquery', 'underscore', 'services/storage/localstorage'],
function($, _, Storage) {
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Workbook collection
     *
     * @singleton
     */
    var WorkbookCollection = function() {
        this.collection = [];
    };

    /**
     * Return the first worksheet in the collection.
     *
     * @return {WorksheetModel}
     */
    WorkbookCollection.prototype.first = function() {
        return this.collection[0];
    };

    /**
     * Get the worksheet collection.
     *
     * @return {Promise} Returns the current collection
     */
    WorkbookCollection.prototype.get = function() {
        Promise.resolve(this.collection);
    };

    /**
     * Get the requested worksheet object based on the worksheet ID.
     * We first check the collection for an existing entry. If it's
     * not there already, we create the object from scratch.
     *
     * @param  {Number}   workbookId  The Worksheet ID
     * @return {Object}               Worksheet object
     */
    WorkbookCollection.prototype.getModel = function(workbookId) {
        return _.findWhere(this.collection, {
            id: workbookId
        });
    };

    WorkbookCollection.prototype.getWorksheetById = function(worksheetId) {
        var worksheets = this.getWorksheets();

        return _.findWhere(worksheets, {
            id: worksheetId
        });
    };

    WorkbookCollection.prototype.getWorksheets = function() {
        var worksheets = [];

        _.each(this.collection, function(workbook) {
            if (workbook.worksheets.length > 0) {
                _.each(workbook.worksheets, function(worksheet) {
                    worksheets.push(worksheet);
                });
            }
        });

        return worksheets;
    };

    /**
     * Add a workbook to our collection
     *
     * @param  {Object} workbook  A workbook object
     * @return {Object}           Workbook collection instance
     */
    WorkbookCollection.prototype.add = function(workbook) {
        this.collection.push(workbook);

        return this;
    };

    /**
     * Add a worksheet to a workbook
     *
     * @param  {Object} workbook  A workbook object
     * @return {Object}           Workbook collection instance
     */
    WorkbookCollection.prototype.addWorksheetToWorkbook = function(workbook, worksheet) {
        workbook = this.getModel(workbook.id);
        workbook.worksheets.push(worksheet);

        return workbook;
    };

    /**
     * Merge an array of workbooks with our current workbook collection
     *
     * @param  {Array}  workbooks An array of workbook objects
     * @return {Object}            Workbook collection instance
     */
    WorkbookCollection.prototype.merge = function(workbooks) {
        this.collection = this.collection.concat(workbooks);

        return this;
    };

    WorkbookCollection.prototype.update = function(workbook) {
        var workbookIndex = _.findIndex(this.collection, this.getModel(workbook.id));
        this.collection[workbookIndex] = workbook;

        return this;
    };

    WorkbookCollection.prototype.updateWorksheet = function(worksheet) {
        var workbook = this.getModel(worksheet.workbook_id);
        var workbookIndex = _.findIndex(this.collection, workbook);
        var worksheetIndex = _.findIndex(workbook.worksheets, this.getWorksheetById(worksheet.id));
        this.collection[workbookIndex].worksheets[worksheetIndex] = worksheet;

        return this;
    };

    /**
     * Gets the current worksheet display options from storage.
     * If there's none, it creates the entry with default options.
     *
     * @param  {Number} worksheetId      Worksheet ID
     * @param  {Object} worksheetStorage [Optional] storage worksheet object
     * @return {Object}                  Worksheet options
     */
    WorkbookCollection.prototype.getWorksheetOptions = function(worksheetId, worksheetStorage) {
        worksheetStorage = worksheetStorage || Storage.getItem('worksheet');
        var worksheetOptions;

        // If there's no `worksheet` storage item, create it
        if (_.isNull(worksheetStorage)) {
            Storage.createItem('worksheet');
        }

        worksheetOptions = worksheetStorage[worksheetId];

        // Set the default options if not set yet
        if (_.isUndefined(worksheetOptions)) {
            worksheetStorage[worksheetId] = {
                sort: 'label:asc',
                outliers: false
            };
        }

        return worksheetStorage[worksheetId];
    };

    WorkbookCollection.prototype.updateWorksheetOptions = function(worksheetId, options) {
        var worksheetStorage = Storage.getItem('worksheet');
        var worksheetOptions = this.getWorksheetOptions(worksheetId, worksheetStorage);

        worksheetOptions.sort = options.sort;
        worksheetOptions.outliers = options.outliers;

        Storage.saveItem('worksheet', worksheetStorage);

        return this;
    };

    /**
     * Remove a single workbook from the collection
     * @param  {Number} workbookId  Workbook id
     * @return {Object}             Workbook collection instance
     */
    WorkbookCollection.prototype.remove = function(workbookId) {
        this.collection = _.without(this.collection, this.getModel(workbookId));

        return this;
    };

    WorkbookCollection.prototype.removeWorksheetFromWorkbook = function(workbook, worksheet) {
        workbook = this.getModel(workbook.id);
        worksheet = this.getWorksheetById(worksheet.id);

        workbook.worksheets = _.without(workbook.worksheets, worksheet);

        return workbook;
    };

    instance = new WorkbookCollection();

    return instance;
});
