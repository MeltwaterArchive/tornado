define(['jquery', 'mustache', 'modallica', 'selectize', 'buzzkill', 'collections/recording', 'collections/workbook', 'plugins/scrollfoo', 'router', 'views/base'],
function($, Mustache, Modallica, selectize, Buzzkill, RecordingCollection, WorkbookCollection, ScrollFoo, Router, View) {
    'use strict';

    /**
     * Project workbook sidebar item constructor
     *
     * @param       {Object} data Project workbooks
     * @description               Renders the workbook list on the sidebar
     */
    var ProjectWorkbookSidebarView = View.extend({
        el: '[data-tornado-view="workbook-sidebar-items"]',
        template: '[data-tornado-template="workbook-sidebar-item"]',

        // ScrollFoo instance
        scrollfoo: $.noop,

        sidebarEl: '.workbook-sidebar',
        sidebarToggleButtonEl: '[data-workbook-sidebar-toggle]',
        sidebarOpenClass: 'workbook-sidebar--open',

        workbookItemEl: '.workbook',
        workbookInfoEl: '.workbook__info',
        activeSidebarWorkbookItemClass: 'workbook--active',
        workbookIdAttribute: 'data-workbook-id',

        editWorkbookButton: '.workbook__action--edit',

        // `Create workbook` modal declarations
        selectOptionTemplate: '[data-tornado-template="workbook-create-modal-select-option-item"]',

        formEl: '[data-form="create-workbook"]',
        nameEl: '.create-workbook__name',
        selectEl: '.create-workbook__select',
        selectElDisabledClass: 'create-workbook__select--disabled',
        recordingEl: '.create-workbook__select--recording',

        modalCreateWorkbookButton: '.modallica--workbook-create-modal [data-modallica-action="submit"]',

        // `Edit workbook` declarations
        modalEditEl: '.modallica--workbook-edit-modal',
        modalEditInputNameEl: '#edit-workbook-name',
        modalEditTemplate: '[data-tornado-template="workbook-edit-modal-name-input"]',
        modalEditTemplateAppendEl: '.modallica--workbook-edit-modal [data-form-field="name"]',

        modalEditSubmitButton: '.modallica--workbook-edit-modal [data-modallica-action="submit"]',
        modalEditDeleteButton: '.modallica--workbook-edit-modal [data-modallica-action="confirm"]',

        // Storing the workbook model we're trying to edit
        editWorkbookData: {},

        // Holds the select and input values
        dataObject: {
            name: null,
            recording_id: null
        },

        bindEvents: function() {
            var _this = this;

            $(this.el).on('click.workbooksidebar', this.workbookInfoEl, function(ev) {
                var $workbookItem = $(this).parent();
                var targetWorkbook = _.findWhere(WorkbookCollection.collection, {
                    id: $workbookItem.attr(_this.workbookIdAttribute)
                });

                // Trigger workbook switch
                $(document).trigger('switch.workbooksidebar', [{
                    prevWorkbook: _this.data.workbook,
                    targetWorkbook: targetWorkbook
                }]);

                if (targetWorkbook.worksheets.length > 0) {
                    Router.navigateTo(
                        '/projects/' + _this.data.project.id + '/worksheet/' + targetWorkbook.worksheets[0].id
                    );
                } else {
                    Router.navigateTo('/projects/' + _this.data.project.id + '/create-worksheet/' + targetWorkbook.id);
                }
            });

            $('body').on('click.workbooksidebar', this.sidebarToggleButtonEl, function() {
                this.toggleSidebar();
            }.bind(this));

            $('body').on('close.workbooksidebar', function() {
                this.closeSidebar();
            }.bind(this));

            // `Create workbook` events
            $('body').on('keyup.workbooksidebar', this.nameEl, function() {
                var nameVal = $(this).val();
                _this.dataObject.name = (nameVal === '')
                    ? null
                    : nameVal;

                _this.handleSubmitButtonState();
            });

            $('body').on('change.workbooksidebar', this.recordingEl, function() {
                var recordingVal = $(this).val();
                _this.dataObject.recording_id = (recordingVal === '') ? null : parseInt(recordingVal, 10);

                _this.handleSubmitButtonState();
            });

            $('body').on('click.workbooksidebar', this.modalCreateWorkbookButton, function() {
                _this.doCreateWorkbook();
            });

            // Event fired when the modal for workbook create is triggered
            $(document).on('workbook-create-modal:ready.modallica', function() {
                _this
                    .renderCreateModalElements()
                    .handleSubmitButtonState();
            });

            // Event fired when the modal for workbook edit is triggered
            $(document).on('workbook-edit-modal:ready.modallica', function() {
                _this.renderModalEditTemplate();
            });

            $(document).on('workbook-edit-modal:hide.modallica', function() {
                _this.editWorkbookData = {};
            });

            $('body').on('click.workbooksidebar', this.modalEditSubmitButton, function() {
                var newWorkbookName = $(_this.modalEditInputNameEl).val();

                _this.doEditWorkbook(newWorkbookName);
            });

            $('body').on('click.workbooksidebar', this.modalEditDeleteButton, function() {
                _this.doDeleteWorkbook();
            });

            $(this.el).on('click.workbooksidebar', this.editWorkbookButton, function(ev) {
                ev.stopPropagation();

                var editWorkbookId = $(this).closest(_this.workbookItemEl).attr(_this.workbookIdAttribute);

                _this.editWorkbookData = WorkbookCollection.getModel(editWorkbookId);

                Modallica.show($(this));
            });

            $(this.el).find('[data-workbook-action="export"]').on('click', function (evt) {
                window.location = $(evt.target).attr('data-href');
            });

            return this;
        },

        unbindEvents: function() {
            $(this.el).off('.workbooksidebar');
            $(document).off('workbook-create-modal:ready.modallica');
            $(document).off('workbook-edit-modal:ready.modallica');
            $(document).off('workbook-edit-modal:hide.modallica');
            $('body').off('.workbooksidebar');

            return this;
        },

        openSidebar: function() {
            $(this.sidebarEl).addClass(this.sidebarOpenClass);
        },

        closeSidebar: function() {
            $(this.sidebarEl).removeClass(this.sidebarOpenClass);
        },

        toggleSidebar: function() {
            if ($(this.sidebarEl).hasClass(this.sidebarOpenClass)) {
                this.closeSidebar();
            } else {
                this.openSidebar();
            }
        },

        /**
         * Get the workbook ID from the current href and highlight the sidebar item
         */
        highlightActiveWorkbookItem: function() {
            $(this.workbookItemEl).removeClass(this.activeSidebarWorkbookItemClass);
            $('[data-workbook-id="' + this.data.workbook.id + '"]').addClass(this.activeSidebarWorkbookItemClass);

            return this;
        },

        initializeScrollbar: function() {
            var realParentHeight = function() {
                var workbookHeight = 0;

                _.each($('.workbook'), function(workbook) {
                    workbookHeight += $(workbook).outerHeight();
                });

                return workbookHeight;
            };

            this.scrollfoo = new ScrollFoo({
                parentEl: '.scrollfoo__parent--workbooks',
                scrollerEl: '.scrollfoo__scroller--workbooks',
                visibleParentHeight: function() {
                    var visibleParentHeight = window.innerHeight - $('.workbook-sidebar__section .scrollfoo__content-wrapper').offset().top;

                    return visibleParentHeight;
                }.bind(this),
                realParentHeight: function() {
                    return $('.scrollfoo__parent--workbooks').outerHeight();
                }
            });

            return this;
        },

        /**
         * Disable/Enable the submit button
         */
        handleSubmitButtonState: function() {
            if (this.isReadyToCreate()) {
                $(this.modalCreateWorkbookButton).removeAttr('disabled');
            } else {
                $(this.modalCreateWorkbookButton).attr('disabled', '')
            }
        },

        /**
         * Check if the data is sufficient to create
         * a new workbook for the project
         * @return {Boolean} Ready or not?
         */
        isReadyToCreate: function() {
            var isReady = true;

            for (var key in this.dataObject) {
                if (_.isNull(this.dataObject[key])) {
                    isReady = false;
                }
            }

            return isReady;
        },

        /**
         * Fetches the recording collection
         */
        getRecordings: function() {
            var _this = this;

            return new Promise(function(resolve, reject) {
                RecordingCollection.get(_this.data.project.id)
                    .then(function(results) {
                        _this.data.recordings = results;

                        resolve(_this);
                    });
            });
        },

        /**
         * `post` to create the new workbook
         */
        doCreateWorkbook: function() {
            var _this = this;

            // Clear errors before `post`ing
            Buzzkill.clearForm($(_this.formEl));

            $.post('/api/project/' + this.data.project.id + '/workbook', this.dataObject)
                .done(function(response) {
                    var workbook = response.data.workbook;

                    // also join the created worksheets (if any)
                    workbook.worksheets = response.data.worksheets;

                    // Add the new workbook to our collection
                    WorkbookCollection.add(workbook);

                    // Trigger an update of the workbook sidebar
                    $('body').trigger('createworkbook.project', [{
                        workbook: workbook,
                        prevWorkbook: _this.data.workbook
                    }]);

                    _this.pageTitle.update({
                        workbookName: workbook.name
                    });

                    Modallica.hide();

                    // if there is already a worksheet in this workbook, then navigate to it
                    var navigateToUrl = workbook.worksheets.length
                        ? '/projects/' + _this.data.project.id + '/worksheet/' + workbook.worksheets[0].id
                        : '/projects/' + _this.data.project.id + '/create-worksheet/' + workbook.id;
                    Router.navigateTo(navigateToUrl);
                })
                .fail(function(error) {
                    Buzzkill.form($(_this.formEl), error.responseJSON.meta);

                    throw new Error('! [Create Workbook] ' + error.status + ': ' + error.statusText);
                });
        },

        /**
         * Edits a workbook (rename)
         *
         * @return {Object} View instance
         */
        doEditWorkbook: function(workbookName) {
            var _this = this;
            var data = JSON.stringify({
                name: workbookName,
                recording_id: this.editWorkbookData.recording_id
            });
            var endpoint = '/api/project/' + this.data.project.id + '/workbook/' + this.editWorkbookData.id;

            $.ajax(endpoint, {
                type: 'PUT',
                data: data,
                contentType: 'application/json'
            }).done(function(response) {
                WorkbookCollection.update(response.data.workbook);

                $('[data-workbook-id="' + response.data.workbook.id + '"] .workbook__title').text(workbookName);

                if (this.editWorkbookData.id === this.data.workbook.id) {
                    this.pageTitle.update({
                        workbookName: response.data.workbook.name
                    });
                }

                Modallica.hide();
            }.bind(this))
            .fail(function(error) {
                throw new Error('! [Workbook view ~ action: EDIT] ' + error.status + ': ' + error.statusText);
            });

            return this;
        },

        /**
         * Deletes a workbook
         *
         * @return {Object} View instance
         */
        doDeleteWorkbook: function() {
            var endpoint = '/api/project/' + this.data.project.id + '/workbook/' + this.editWorkbookData.id;

            $.ajax(endpoint, {
                type: 'DELETE',
                contentType: 'application/json'
            }).done(function(response) {
                // Trigger a Workbook delete
                $('body').trigger('deleteworkbook.project', [{
                    workbook: this.editWorkbookData
                }]);

                WorkbookCollection.remove(this.editWorkbookData.id);
                Modallica.hide();

                if (WorkbookCollection.collection.length === 0) {
                    this.closeSidebar();
                }

                Router.navigateTo('/projects/' + this.data.project.id);
            }.bind(this))
            .fail(function(error) {
                throw new Error('! [Worksheet view ~ action: DELETE] ' + error.status + ': ' + error.statusText);
            });

            return this;
        },

        /**
         * Renders the recording select options
         *
         * @param  {String} template Template html
         * @return {Object}          View instance
         */
        renderRecordings: function(template) {
            var recordingsHtml = '';

            _.each(this.data.recordings, function(recording) {
                recordingsHtml += Mustache.render(template, {
                    id: recording.id,
                    name: recording.name
                });
            }.bind(this));

            $(this.recordingEl).append(recordingsHtml);

            return this;
        },

        renderCreateModalElements: function() {
            var selectOptionTemplate = $(this.selectOptionTemplate).html();

            this.loader.load($(this.modalCreateWorkbookButton));

            this.getRecordings()
                .then(function() {
                    this.renderRecordings(selectOptionTemplate);

                    $(this.selectEl)
                        .removeClass(this.selectElDisabledClass)
                        .selectize();

                    $(this.nameEl).focus();

                    this.loader.unload($(this.modalCreateWorkbookButton));
                    $(this.modalCreateWorkbookButton).removeAttr('data-loader');
                }.bind(this));

            return this;
        },

        renderModalEditTemplate: function() {
            var template = $(this.modalEditTemplate).html();
            template = Mustache.render(template, this.editWorkbookData);

            $(this.modalEditTemplateAppendEl).html(template);
        },

        render: function() {
            if (WorkbookCollection.collection.length > 0) {
                var projectWorkbookSidebarItemTemplate = $(this.template).html();
                var projectWorkbookSidebarItemsTemplate = '';

                _.each(WorkbookCollection.collection, function(workbook, index) {
                    projectWorkbookSidebarItemsTemplate += Mustache.render(projectWorkbookSidebarItemTemplate, workbook);
                }.bind(this));

                $(this.el).html(projectWorkbookSidebarItemsTemplate);

                this
                    .highlightActiveWorkbookItem()
                    .initializeScrollbar();
            }

            this.finalizeView();
        }
    });

    return ProjectWorkbookSidebarView;
});
