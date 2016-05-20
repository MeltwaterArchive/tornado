define([
    'jquery', 
    'promise', 
    'modallica', 
    'collections/workbook', 
    'views/global/menu',
    'views/project/workbook-sidebar', 
    // ----------------------------
    'models/WorkbookModel',
    'views/workbook/WorkbookView'
], function(
    $, 
    Promise, 
    Modallica, 
    WorkbookCollection, 
    Menu, 
    ProjectWorkbookSidebarView, 
    // ----------------------------
    WorkbookModel,
    WorkbookView
) {
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    var ProjectController = function() {
        this.data = {};
        this.loaded = false;
        this.bound = false;
        this.bindEvents();
    };

    ProjectController.prototype.bindEvents = function() {
        var _this = this;

        // Re-render the worksheet list
        $('body').on('createworksheet.project editworksheet.project', function(ev, data) {
            if (this.wv.model.get('worksheets')) {
                // add to the new collection
                this.wv.model.get('worksheets').add(data.worksheet);
            }
            this.renderProjectWorksheetList(data.worksheet);
            // add to the new collection
        }.bind(this));

        // Update the project workbook collection and re-render the sidebar
        $('body').on('createworkbook.project', function(ev, data) {
            this.data.workbookId = data.workbook.id;

            this.renderProjectWorkbookSidebar(data.workbook.id);
        }.bind(this));
    };

    /**
     * Get project data from the server.
     *
     * @return {Promise(ProjectController)}
     */
    ProjectController.prototype.getData = function(projectId) {
        var _this = this;

        return new Promise(function(resolve, reject) {
            if (_this.loaded === false) {
                $.get('/projects/' + projectId)
                    .done(function(response) {
                        _this.data.project = response.data.project;
                        _this.loaded = true;

                        WorkbookCollection.merge(response.data.workbooks);

                        resolve(_this);
                    })
                    .fail(function(error) {
                        reject(error);
                    });
            } else {
                resolve(_this);
            }
        });
    };

    /**
     * Removes the initial loading indicator.
     * This spinner & overlay is shown right after navigating
     * from the non-spa to the spa app for the first time.
     */
    ProjectController.prototype.removeLoadingIndicator = function() {
        $('body').removeClass('tornado-not-ready');

        setTimeout(function() {
            $('.tornado-loading-overlay').remove();
        }, 800);
    };

    ProjectController.prototype.resetProjectContentViews = function() {
        $('[data-tornado-view="page-header"]').html('');
        $('[data-tornado-view="page-footer"]').html('');

        return this;
    };

    ProjectController.prototype.renderProjectWorksheetList = function(worksheet) {
        if (this.wv === undefined || this.wv.model.get('id') !== this.data.workbookId) {

            var wm = new WorkbookModel(WorkbookCollection.getModel(this.data.workbookId));
            // set the project name for the breadcrumb
            wm.set('project_name', this.data.project.name);

            if (this.wv) {
                this.wv.remove();
            }

            this.wv = new WorkbookView({
                model: wm
            });

        } else {
            var selected = this.wv.model.get('worksheets').findWhere({'selected': true});
            if (selected) {
                selected.set({'selected': false}, {'silent': true});
            }
        } 

        if (this.wv.model.get('worksheets') && this.wv.model.get('worksheets').length > 0) {
            // lets make sure the correct worksheet is selected, this will trigger a render
            this.wv.model.get('worksheets').findWhere({'id': worksheet.id}).set('selected', true);
        } else {
            this.wv.render();
        }

        return this;
    };

    ProjectController.prototype.renderProjectWorkbookSidebar = function(workbookId, openSidebar) {
        openSidebar = openSidebar || false;
        var projectWorkbookSidebarView = new ProjectWorkbookSidebarView({
            project: this.data.project,
            workbook: WorkbookCollection.getModel(workbookId) || null
        });

        projectWorkbookSidebarView.render();

        if (openSidebar) {
            projectWorkbookSidebarView.openSidebar();
        }

        return this;
    };

    instance = new ProjectController();

    return instance;
});
