define(['jquery', 'mustache', 'router', 'views/global/menu', 'modallica', 'buzzkill', 'collections/workbook', 'services/http/error-formatter'],
function($, Mustache, Router, Menu, Modallica, Buzzkill, WorkbookCollection, ErrorFormatter) {
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Create Worksheet
     *
     * @description Takes care of worksheet creation. Renders the modal,
     *              does all the necessary client-side checks etc. Works
     *              for both creating a new worksheet and an exploration.
     */
    var CreateWorksheet = function() {
        this.el = {
            form: '[data-form="create-worksheet"]',
            name: '.create-worksheet__name',
            chartType: '[data-create-worksheet-chart-type]',
            createWorksheetButton: '.worksheet-list__create-worksheet',
            modalCreateWorksheetButton: '.modallica--worksheet-create-modal [data-modallica-action="submit"]',
            modalCreateExplorationWorksheetButton: '.modallica--worksheet-create-exploration-modal [data-modallica-action="submit"]'
        };

        this.attributes = {
            chartType: 'data-create-worksheet-chart-type',
            analysisType: 'data-create-worksheet-analysis-type'
        };

        this.classes = {
            chartTypeActive: 'create-worksheet__chart-type--active'
        };

        this.events = {
            typing: 'keyup.createworksheet',
            click: 'click.createworksheet'
        };

        // Holds the select and input values
        this.worksheetData = {
            name: null,
            chart_type: null,
            type: 'freqDist'
        };

        this.$body = $('body');

        this.bindEvents();
    };

    CreateWorksheet.prototype.bindEvents = function() {
        var _this = this;

        // `Create worksheet` events
        this.$body.on(this.events.typing, this.el.name, function() {
            var nameVal = $(this).val();
            _this.worksheetData.name = (nameVal === '')
                ? null
                : nameVal;

            _this.handleSubmitButtonState();
        });

        this.$body.on(this.events.click, this.el.chartType, function() {
            _this.selectChartType($(this));
        });

        this.$body.on(this.events.click, this.el.modalCreateWorksheetButton, function() {
            _this.doCreateWorksheet();
        });

        this.$body.on(this.events.click, this.el.modalCreateExplorationWorksheetButton, function() {
            _this.doCreateWorksheet();
        });

        // Event fired when the modal for worksheet create is triggered
        $(document).on('worksheet-create-modal:ready.modallica', function() {
            _this
                .resetData()
                .handleSubmitButtonState();
        });

        $(document).on('worksheet-create-exploration-modal:ready.modallica', function() {
            $(_this.el.name).val(_this.worksheetData.name);
            _this.handleSubmitButtonState();
        });

        return this;
    };

    CreateWorksheet.prototype.unbindEvents = function() {
        this.$body.off('.createworksheet');
        $(document).off('worksheet-create-modal:ready.modallica');
        $(document).off('worksheet-create-exploration-modal:ready.modallica');

        return this;
    };

    CreateWorksheet.prototype.resetData = function() {
        this.worksheetData = {
            name: null,
            chart_type: null,
            type: 'freqDist'
        };

        return this;
    };

    CreateWorksheet.prototype.selectChartType = function($chartType) {
        var chartTypeVal;
        var analysisTypeVal;

        if ($chartType.hasClass(this.classes.chartTypeActive)) {
            analysisTypeVal = chartTypeVal = null;

            $(this.el.chartType).removeClass(this.classes.chartTypeActive);
        } else {
            chartTypeVal = $chartType.attr(this.attributes.chartType);
            analysisTypeVal = $chartType.attr(this.attributes.analysisType);

            $(this.el.chartType).removeClass(this.classes.chartTypeActive);
            $chartType.addClass(this.classes.chartTypeActive);
        }

        this.worksheetData.chart_type = chartTypeVal;
        this.worksheetData.type = analysisTypeVal;

        this.handleSubmitButtonState();
    };

    /**
     * Disable/Enable the submit button
     */
    CreateWorksheet.prototype.handleSubmitButtonState = function() {
        if (this.isReadyToCreate()) {
            $(this.el.modalCreateWorksheetButton).removeAttr('disabled');
        } else {
            $(this.el.modalCreateWorksheetButton).attr('disabled', '')
        }
    };

    /**
     * Check if the data is sufficient to create
     * a new worksheet for the project
     *
     * @return {Boolean} Ready or not?
     */
    CreateWorksheet.prototype.isReadyToCreate = function() {
        var isReady = true;

        for (var key in this.worksheetData) {
            if (_.isNull(this.worksheetData[key])) {
                isReady = false;
            }
        }

        return isReady;
    };

    /**
     * `post` to create the new worksheet
     */
    CreateWorksheet.prototype.doCreateWorksheet = function() {
        var _this = this;
        var endpoint;

        // Clear errors before `post`ing
        Buzzkill.clearForm($(this.el.form));

        require(['controllers/project'], function(ProjectController) {
            var postData = {};

            // Posting to the right endpoint, depending on the
            // type of the new worksheet (new or exploration)
            if (_.isUndefined(_this.worksheetData.explore)) {
                endpoint = '/api/project/' + ProjectController.data.project.id + '/worksheet';
                _this.worksheetData.workbook_id = ProjectController.data.workbookId;
                postData = _this.worksheetData;
            } else {
                endpoint = '/api/project/' + ProjectController.data.project.id + '/worksheet/' + _this.worksheetData.worksheetId + '/explore';
                postData = _.pick(_.clone(_this.worksheetData), ['name', 'chart_type', 'type', 'explore', 'start', 'end']);
            }

            $.post(endpoint, postData)
                .done(function(response) {
                    var workbook = WorkbookCollection.getModel(ProjectController.data.workbookId);

                    // Update the Workbook collection
                    WorkbookCollection.addWorksheetToWorkbook(workbook, response.data.worksheet);
                    // update the new worksheet collection

                    if (!_.isUndefined(_this.worksheetData.worksheetId)) {
                        delete _this.worksheetData.worksheetId;
                    }

                    // Trigger an update of the worksheet list
                    _this.$body.trigger('createworksheet.project', [{
                        worksheet: response.data.worksheet
                    }]);

                    _this.$body.trigger('close.workbooksidebar');

                    Modallica.hide();
                    //Menu.show();

                    // Redirect to the dimensions view
                    require(['router'], function(Router) {
                        var worksheetRoute = '/projects/' + response.data.project.id + '/worksheet/' + response.data.worksheet.id;

                        // Timeseries can't have dimensions, bro.
                        if (response.data.worksheet.chart_type === 'timeseries') {
                            Router.navigateTo(worksheetRoute + '/filters');
                        } else {
                            Router.navigateTo(worksheetRoute + '/dimensions');
                        }
                    });
                })
                .fail(function(error) {
                    if (400 === error.status) {
                        Buzzkill.form($(_this.el.form), error.responseJSON.meta);
                    } else {
                        ErrorFormatter.format(error);
                    }

                    throw new Error('! [Create Worksheet] ' + error.status + ': ' + error.statusText);
                });
        });
    };

    /**
     * Renders the exploration modal
     *
     * @param  {Object} explorationData Exploration data
     * @return {Object}                 CreateWorksheet instance
     */
    CreateWorksheet.prototype.renderExplorationModal = function(explorationData) {
        this.resetData();

        this.worksheetData = $.extend({}, this.worksheetData, explorationData);

        Modallica.render({
            title: 'New Worksheet from explore',
            templateName: 'worksheet-create-exploration-modal'
        });

        return this;
    };

    instance = new CreateWorksheet();

    return instance;
});
