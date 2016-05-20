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
     * @param  {Number} worksheet      Worksheet ID
     * @return {Object}                  Worksheet options
     */
    WorkbookCollection.prototype.getWorksheetOptions = function(worksheetId) {

        var worksheet = this.getWorksheetById(worksheetId);
        var worksheetOptions = worksheet['display_options'];

        // Set the default options if not set yet
        if (_.isNull(worksheetOptions) || _.isEmpty(worksheetOptions)) {
            worksheetOptions = { // See NEV-597 - default sort for histograms should be size desc
                sort: (worksheet.chart_type === 'histogram') ? 'size:desc': 'label:asc',
                outliers: true
            };
        }

        return worksheetOptions;
    };

    WorkbookCollection.prototype.updateWorksheetOptions = function(projectId, worksheetId, options) {

        var worksheet = this.getWorksheetById(worksheetId);
        worksheet['display_options'] = options;
        var endpoint = '/api/project/' + projectId + '/worksheet/' + worksheetId;
        var data = {
            'workbook_id': worksheet['workbook_id'],
            'name': worksheet['name'],
            'display_options': options
        };

        $.ajax(endpoint, {
            type: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify(data),
            dataType: 'json'
        }).done(function() {
            this.updateWorksheet(worksheet);
        }.bind(this));

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
