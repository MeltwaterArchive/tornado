define(['jquery', 'mustache', 'views/base', 'router', 'modallica', 'buzzkill', 'collections/workbook', 'controllers/worksheet', 'views/worksheet/create-worksheet'],
function($, Mustache, View, Router, Modallica, Buzzkill, WorkbookCollection, WorksheetController, CreateWorksheet) {
    'use strict';

    /**
     * Project worksheet list view
     *
     * @param {Object} data Project worksheets
     * @description         Renders the worksheet list on the sidebar (project/worksheet view)
     */
    var worksheetListView = View.extend({
        el: '[data-tornado-view="worksheet-list"]',
        template: '[data-tornado-template="worksheet-list"]',

        worksheetListEl: '.worksheet-list',
        worksheetListElDisabledClass: 'worksheet-list--disabled',

        worksheetItemEl: '.worksheet-list__item',
        worksheetItemActiveClass: 'worksheet-list__item--active',
        worksheetIdAttribute: 'data-worksheet-id',

        worksheetItemActionEl: '[data-worksheet-action]',
        worksheetItemActionAttr: 'data-worksheet-action',
        worksheetIdAttr: 'data-worksheet-id',

        // `Edit worksheet` declarations
        modalEditEl: '.modallica--worksheet-edit-modal',
        modalEditInputNameEl: '#edit-worksheet-name',
        modalEditTemplate: '[data-tornado-template="worksheet-edit-modal-name-input"]',
        modalEditTemplateAppendEl: '.modallica--worksheet-edit-modal [data-form-field="name"]',

        modalEditSubmitButton: '.modallica--worksheet-edit-modal [data-modallica-action="submit"]',
        modalEditDeleteButton: '.modallica--worksheet-edit-modal [data-modallica-action="confirm"]',

        // `Display options` declarations
        modalOptionsEl: '.modallica--worksheet-options-modal',
        modalOptionsApplyButton: '.modallica--worksheet-options-modal [data-modallica-action="submit"]',

        bindEvents: function() {
            var _this = this;

            $(this.el).on('click.listworksheet', this.worksheetItemEl, function(ev) {
                if ($(ev.target).attr('data-dropdown-toggle') !== '') {
                    var $worksheetItem = $(this);

                    if ($worksheetItem.hasClass(_this.worksheetItemActiveClass)) {
                        return false;
                    }

                    $(_this.worksheetItemEl).removeClass(_this.worksheetItemActiveClass);
                    $worksheetItem.addClass(_this.worksheetItemActiveClass);

                    Router.navigateTo('/projects/' + _this.data.project.id + '/worksheet/' + $worksheetItem.attr(_this.worksheetIdAttribute));
                }
            });

            $(this.el).on('click.listworksheet', this.worksheetItemActionEl, function(ev) {
                ev.stopPropagation();

                _this.dropdownToggle.closeAll();
                _this.onWorksheetAction($(this));
            });

            $('body').on('click.listworksheet', this.modalEditSubmitButton, function() {
                var newWorksheetName = $(_this.modalEditInputNameEl).val();

                _this.doEditWorksheet(newWorksheetName);
            });

            // Event fired when the modal for worksheet edit is triggered
            $(document).on('worksheet-edit-modal:ready.modallica', function() {
                _this.renderModalEditTemplate();
            });

            // Event fired when the modal for worksheet options is triggered
            $(document).on('worksheet-options-modal:ready.modallica', function() {
                _this.renderModalOptionsTemplate();
            });

            $('body').on('click.listworksheet', this.modalOptionsApplyButton, function() {
                _this.applyDisplayOptions();
            });

            $('body').on('click.listworksheet', this.modalEditDeleteButton, function() {
                _this.doDeleteWorksheet();
            });

            return this;
        },

        unbindEvents: function() {
            $(this.el).off('.listworksheet');
            $(document).off('worksheet-edit-modal:ready.modallica');
            $(document).off('worksheet-options-modal:ready.modallica');
            $('body').off('.listworksheet');

            return this;
        },

        /**
         * Highlight the active worksheet
         */
        highlightActiveWorksheetListItem: function() {
            $(this.worksheetItemEl)
                .filter('[' + this.worksheetIdAttribute + '="' + this.data.worksheet.id + '"]')
                    .addClass(this.worksheetItemActiveClass);

            return this;
        },

        /**
         * Method called when clicking on an action. Includes
         * core actions like `delete`, `rename`, `export` etc.
         *
         * @param  {jQuery} $worksheetAction Worksheet action $element
         */
        onWorksheetAction: function($worksheetAction) {
            var action = $worksheetAction.attr(this.worksheetItemActionAttr);
            var worksheetId = $worksheetAction.attr(this.worksheetIdAttr);

            switch (action) {
                case 'options':
                case 'edit':
                    Modallica.show($worksheetAction);
                    break;
                case 'export':
                    this.exportWorksheet();
                    break;
            }
        },

        /**
         * Exports a worksheet
         *
         * @return {Object}             View instance
         */
        exportWorksheet: function() {
            window.location = '/api/project/' + this.data.project.id + '/worksheet/' + this.data.worksheet.id + '/export';

            return this;
        },

        /**
         * Edits a worksheet (rename)
         *
         * @return {Object} View instance
         */
        doEditWorksheet: function(worksheetName) {
            var _this = this;
            var data = JSON.stringify({
                name: worksheetName
            });
            var endpoint = '/api/project/' + this.data.project.id + '/worksheet/' + this.data.worksheet.id;

            $.ajax(endpoint, {
                type: 'PUT',
                data: data,
                contentType: 'application/json'
            }).done(function(response) {
                WorkbookCollection.updateWorksheet(response.data.worksheet);

                $('body').trigger('editworksheet.project', [{
                    worksheet: response.data.worksheet
                }]);

                Modallica.hide();
            }.bind(this))
            .fail(function(error) {
                throw new Error('! [Worksheet view ~ action: EDIT] ' + error.status + ': ' + error.statusText);
            });

            return this;
        },

        /**
         * Deletes a worksheet
         *
         * @return {Object} View instance
         */
        doDeleteWorksheet: function() {
            var _this = this;
            var endpoint = '/api/project/' + this.data.project.id + '/worksheet/' + this.data.worksheet.id;

            $.ajax(endpoint, {
                type: 'DELETE',
                contentType: 'application/json'
            }).done(function(response) {
                var workbook = WorkbookCollection.removeWorksheetFromWorkbook(this.data.workbook, this.data.worksheet);

                Modallica.hide();

                if (workbook.worksheets.length > 0) {
                    Router.navigateTo('/projects/' + this.data.project.id + '/worksheet/' + workbook.worksheets[0].id);
                } else {
                    Router.navigateTo('/projects/' + this.data.project.id + '/create-worksheet/' + workbook.id);
                }
            }.bind(this))
            .fail(function(error) {
                throw new Error('! [Worksheet view ~ action: DELETE] ' + error.status + ': ' + error.statusText);
            });

            return this;
        },

        applyDisplayOptions: function() {
            var $el = $(this.modalOptionsEl);
            var sortDimensions = $el.find('[name="sort-dimensions"]').val();
            var displayOutliers = ($el.find('[name="outliers"]:checked').length) ? true : false;

            WorksheetController.view.dimensionSortBy = sortDimensions[0];
            WorksheetController.view.dimensionSortOrder = sortDimensions[1];

            WorkbookCollection.updateWorksheetOptions(this.data.worksheet.id, {
                sort: sortDimensions,
                outliers: displayOutliers
            });

            WorksheetController.view.setOutliers(displayOutliers);
            WorksheetController.view.renderCharts();
            Modallica.hide();
        },

        renderModalEditTemplate: function() {
            var template = $(this.modalEditTemplate).html();
            template = Mustache.render(template, this.data.worksheet);

            $(this.modalEditTemplateAppendEl).html(template);
        },

        renderModalOptionsTemplate: function() {
            var $el = $(this.modalOptionsEl);
            var worksheetOptions = WorkbookCollection.getWorksheetOptions(this.data.worksheet.id);

            $el.find('select[name="sort-dimensions"]').selectize();

            if (this.data.worksheet.chart_type === 'timeseries') {
                $el.find('[data-form="sort"]').hide();
            }

            if (!this.data.worksheet.secondary_recording_id && !this.data.worksheet.baseline_dataset_id) {
                $el.find('[data-form="outliers"]').hide();
            }

            $el.find('[name="outliers"]').attr('checked', worksheetOptions.outliers);
            $el.find('select[name="sort-dimensions"]')[0].selectize.setValue(worksheetOptions.sort);
        },

        render: function() {
            if (_.isNull(this.data.worksheet)) {
                $(this.worksheetListEl).addClass(this.worksheetListElDisabledClass);

                return;
            }

            var worksheetListItemTemplate = $(this.template).html();
            var worksheetListItemsTemplate = '';
            var workbook = WorkbookCollection.getModel(this.data.worksheet.workbook_id);

            _.each(workbook.worksheets, function(worksheet, index) {
                // don't show display options option when there is
                // no comparison and when worksheet shows timeseries
                // (in such case the options modal would be empty)
                var showOptions = worksheet.baseline_dataset_id || worksheet.secondary_recording_id || worksheet.chart_type !== 'timeseries';
                worksheetListItemsTemplate += Mustache.render(worksheetListItemTemplate, {
                    worksheet: worksheet,
                    showOptions: showOptions
                });
            }.bind(this));

            $(this.el).html(worksheetListItemsTemplate);

            $(this.worksheetListEl).removeClass(this.worksheetListElDisabledClass);

            this
                .highlightActiveWorksheetListItem()
                .finalizeView();
        }
    });

    return worksheetListView;
});
