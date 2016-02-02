define(['jquery', 'promise', 'modallica', 'collections/workbook', 'views/global/menu', 'views/global/page-title', 'views/project/worksheet-list', 'views/project/workbook-sidebar'],
function($, Promise, Modallica, WorkbookCollection, Menu, PageTitle, ProjectWorksheetListView, ProjectWorkbookSidebarView) {
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    var ProjectController = function() {
        this.data = {};
        this.loaded = false;

        this.bindEvents();
    };

    ProjectController.prototype.bindEvents = function() {
        var _this = this;

        // Re-render the worksheet list
        $('body').on('createworksheet.project editworksheet.project', function(ev, data) {
            this.renderProjectWorksheetList(data.worksheet);
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
                        //Menu.data.project = _this.data.project;
                        PageTitle.data.projectName = _this.data.project.name

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
        var projectWorksheetListView = new ProjectWorksheetListView({
            project: this.data.project,
            workbook: WorkbookCollection.getModel(this.data.workbookId),
            worksheet: worksheet
        });

        projectWorksheetListView.render();

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
