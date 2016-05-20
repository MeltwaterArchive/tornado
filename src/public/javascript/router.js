define([
    'config',
    'jquery',
    'underscore',
    'backbone',
    'promise',
    'models/menu',
    'models/EventTrackerModel',
    'models/WorksheetModel',
    'collections/PostCollection',
    'collections/WorksheetCollection',
    'views/global/menu'
], function(
    config,
    $,
    _,
    Backbone,
    Promise,
    MenuModel,
    ETModel,
    NewWorksheetModel,
    PostCollection,
    WorksheetCollection,
    MenuView
) {
    'use strict';


    ETModel.record('Tornado Loaded');
    ETModel.record('Tornado version: ' + config.version);

    /**
     * Tornado router constructor
     */
    var TornadoRouter = Backbone.Router.extend({
        routes: {
            'projects/:id/create-worksheet/:id': 'goToNoWorksheet',
            'projects/:id/create-workbook': 'goToNoWorkbook',
            'projects/:id/worksheet/:id/dimensions': 'goToDimensions',
            'projects/:id/worksheet/:id/timespan': 'goToTimespan',
            'projects/:id/worksheet/:id/filters': 'goToFilters',
            'projects/:id/worksheet/:id/comparison': 'goToComparison',
            'projects/:id/worksheet/:id': 'goToWorksheet',
            'projects/:id/workbook/:id': 'goToWorkbook',
            'projects/:id': 'goToProject'
        },

        /**
         * Start the routing process and listeners.
         */
        start: function() {
            var _this = this;

            /**
             * Intercept clicks - prevent page reload and let the backbone router do its job
             */
            $('body')
                .on('click.tornado', '[data-page-load]', function(ev) {
                    var url = (_.isNull(this.getAttribute('href')))
                        ? this.getAttribute('data-page-load')
                        : this.getAttribute('href');

                    // If the link is on the same domain
                    if (url.match(/^\//)) {
                        // Ignore modifiers
                        if (!ev.ctrlKey && !ev.metaKey) {
                            _this.navigateTo(url);

                            return false;
                        }
                    }
                });

            Backbone.history.start({
                pushState: true,
                hashChange: false
            });

            // Lets keep a record of all the worksheets we have loaded
            this.worksheetCollection = new WorksheetCollection();
        },

        execute: function(callback, args, name) {
            var _this = this;
            var projectId = args[0];

            this.ensureProject(projectId)
                .then(function() {
                    if (callback) {
                        callback.apply(_this, args);
                    }
                });
        },

        /**
         * Navigate to a URL within the app.
         *
         * @param  {String}  url        URL to navigate to.
         * @param  {Boolean} replace    Replaces the history
         */
        navigateTo: function(url, replace) {
            replace = replace || false;

            return this.navigate(url, {
                trigger: true,
                replace: replace
            });
        },

        ensureProject: function(projectId) {
            var _this = this;

            return new Promise(function(resolve, reject) {
                require(['controllers/project'], function(ProjectController) {
                    ProjectController.getData(projectId)
                        .then(function() {
                            resolve(ProjectController.data.project);
                        }, reject);
                });
            });
        },

        ensureWorksheet: function (projectId, worksheetId) {

            this.ensureMenu(projectId, worksheetId);

            return new Promise(function (resolve, reject) {
                require(['controllers/worksheet'], function (WorksheetController) {
                    this.worksheetController = WorksheetController;
                    resolve(WorksheetController.getData(projectId, worksheetId));
                }.bind(this));
            }.bind(this)).then(function (data) {

                var extra = {
                        'project_id': projectId, 
                        'posts': new PostCollection(data.data.posts)
                    },
                    worksheet = _.merge(data.data.worksheet, extra),
                    nwsm = new NewWorksheetModel(worksheet);

                // if we don't have a model
                if (!this.worksheetController.newModel) {
                    this.worksheetController.newModel = nwsm;
                    this.worksheetCollection.add(nwsm);
                } else if (worksheet.id !== this.worksheetController.newModel.get('id')) {
                    // do we have a model in the collection
                    if (this.worksheetCollection.get(worksheet.id)) {
                        this.worksheetController.newModel = this.worksheetCollection.get(worksheet.id);
                    } else {
                        this.worksheetController.newModel = nwsm;
                        this.worksheetCollection.add(nwsm);
                    }
                }

                return data;
            }.bind(this));
        },

        ensureMenu: function (projectId, worksheetId) {

            if (this.menu === undefined) {
                // create the model and view
                MenuModel.set({
                    projectId: projectId,
                    worksheetId: worksheetId
                });
                this.menu = new MenuView({model: MenuModel});
            } else if (this.menu.model.get('projectId') !== projectId || this.menu.model.get('worksheetId') !== worksheetId) {
                // update the ID's
                this.menu.model.set('projectId', projectId);
                this.menu.model.set('worksheetId', worksheetId);
            }
        },

        setPageAttributeToBody: function(pageName) {
            $('body').attr('data-tornado-page', pageName);
        },

        goToProject: function(projectId) {
            var _this = this;

            // Navigate to the first worksheet in the collection
            // after the project is initialized and data is fetched
            require(['collections/workbook'], function(WorkbookCollection) {
                // Create a new workbook if there are none
                if (WorkbookCollection.collection.length === 0) {
                    // check if we maybe should trigger build of default workbook?
                    _this.ensureProject(projectId)
                        .then(function(project) {
                            // if not a fresh project then navigate to manual workbook creation
                            if (!project.fresh || project.recording_filter != 1) {
                                _this.navigateTo('/projects/' + projectId + '/create-workbook', true);
                                return;
                            }

                            $.ajax({
                                url: '/api/project/' + projectId + '/workbook/default',
                                method: 'POST'
                            }).done(function(response) {
                                var workbook = response.data.workbook;
                                workbook.worksheets = response.data.worksheets;

                                WorkbookCollection.add(workbook);

                                // retrigger navigation so it goes to the first found worksheet
                                _this.goToProject(projectId);
                            }).fail(function() {
                                // if something failed then give up, and redirect to create workbook
                                _this.navigateTo('/projects/' + projectId + '/create-workbook', true);
                            });
                        });
                } else {
                    var worksheets = WorkbookCollection.getWorksheets();

                    // Sort the worksheets by date in order to get the most recent one
                    worksheets.sort(function(x, y) {
                        return x.updated_at - y.updated_at;
                    });

                    // Create a new worksheet if there are none
                    if (worksheets.length === 0) {
                        _this.navigateTo('/projects/' + projectId + '/create-worksheet/' + WorkbookCollection.first().id, true);
                    } else {
                        var worksheetId = worksheets[0].id;

                        _this.ensureWorksheet(projectId, worksheetId)
                            .then(function() {
                                _this.navigateTo('/projects/' + projectId + '/worksheet/' + worksheetId, true);
                            });
                    }
                }
            });
        },

        goToNoWorkbook: function() {
            require(['controllers/project', 'controllers/workbook', 'controllers/worksheet'],
            function(ProjectController, WorkbookController, WorksheetController) {
                this.setPageAttributeToBody('no-workbook');

                ProjectController
                    .resetProjectContentViews()
                    .renderProjectWorksheetList(null)
                    .renderProjectWorkbookSidebar()
                    .removeLoadingIndicator();

                WorksheetController.renderNoWorkbooks();
            }.bind(this));
        },

        goToNoWorksheet: function(projectId, workbookId) {
            require(['controllers/project', 'controllers/workbook', 'controllers/worksheet', 'collections/workbook'],
            function(ProjectController, WorkbookController, WorksheetController, WorkbookCollection) {
                ProjectController.data.workbookId = workbookId;

                //lock workbook
                WorkbookController.lock(projectId, workbookId);

                this.setPageAttributeToBody('no-worksheet');

                ProjectController
                    .resetProjectContentViews()
                    .renderProjectWorksheetList(null)
                    .renderProjectWorkbookSidebar(workbookId, true)
                    .removeLoadingIndicator();

                WorksheetController.renderNoWorksheet(projectId, workbookId);
            }.bind(this));
        },

        goToWorksheet: function(projectId, worksheetId) {

            this.ensureWorksheet(projectId, worksheetId).then(function (data) {
                require(['controllers/project', 'controllers/workbook', 'controllers/worksheet', 'collections/workbook'],
                function(ProjectController, WorkbookController, WorksheetController, WorkbookCollection) {
                    var workbook = WorkbookCollection.getModel(data.data.worksheet.workbook_id);
                    this.setPageAttributeToBody('worksheet');

                    WorksheetController.renderWorksheet();

                    this.menu.model.set({
                        'controller': 'worksheet',
                        'worksheet': data.data.worksheet,
                        'workbook': workbook
                    });
                }.bind(this));
            }.bind(this));
        },

        goToDimensions: function(projectId, worksheetId) {

            this.ensureWorksheet(projectId, worksheetId)
                .then(function(data) {
                    require(['controllers/dimension', 'controllers/workbook'],
                    function(DimensionController, WorkbookController) {
                        //lock workbook
                        WorkbookController.lock(projectId, data.data.workbook.id);
                        this.setPageAttributeToBody('dimensions');
                        new DimensionController(projectId, worksheetId, data.data.workbook.id);
                        this.menu.model.set('controller', 'dimensions');
                    }.bind(this));
                }.bind(this));
        },

        goToTimespan: function(projectId, worksheetId) {
            this.ensureWorksheet(projectId, worksheetId)
                .then(function(data) {
                    require(['controllers/timespan', 'controllers/workbook'],
                    function(TimespanController, WorkbookController) {
                        //lock workbook
                        WorkbookController.lock(projectId, data.data.workbook.id);
                        this.setPageAttributeToBody('timespan');
                        new TimespanController(projectId, worksheetId);
                        this.menu.model.set('controller', 'timespan');
                    }.bind(this));
                }.bind(this));
        },

        goToFilters: function(projectId, worksheetId) {
            this.ensureWorksheet(projectId, worksheetId)
                .then(function(data) {
                    require(['controllers/filters', 'controllers/workbook'],
                    function(FiltersController, WorkbookController) {
                        //lock workbook
                        WorkbookController.lock(projectId, data.data.workbook.id);
                        this.setPageAttributeToBody('filters');
                        var filterController = new FiltersController(projectId, worksheetId);
                        filterController.model = data.newModel;
                        this.menu.model.set('controller', 'filters');
                    }.bind(this));
                }.bind(this));
        },

        goToComparison: function(projectId, worksheetId) {
            this.ensureWorksheet(projectId, worksheetId)
                .then(function(data) {
                    require(['controllers/comparison', 'controllers/workbook'],
                    function(ComparisonController, WorkbookController) {
                        //lock workbook
                        new ComparisonController(projectId, worksheetId);
                        WorkbookController.lock(projectId, data.data.workbook.id);
                        this.setPageAttributeToBody('comparison');
                        this.menu.model.set('controller', 'comparison');
                    }.bind(this));
                }.bind(this));
        },

        /**
         * Go To Workbook
         *
         * There is no concept of a workbook in this structure therefore all we
         * are doing is redirecting to a worksheet.
         * 
         * This will be removed when we rewrite the router to use the new 
         * structure.
         */
        goToWorkbook: function (projectId, workbookId) {

            var WorkbookCollection = require('collections/workbook'),
                workbook = _.findWhere(WorkbookCollection.collection, {
                    id: workbookId
                });

            if (workbook.worksheets.length > 0) {
                this.navigateTo(
                    '/projects/' + projectId + '/worksheet/' +
                    workbook.worksheets[0].id
                );
            } else {
                this.navigateTo(
                    '/projects/' + projectId + '/create-worksheet/' + workbookId
                );
            }
        }
    });

    return new TornadoRouter();
});
