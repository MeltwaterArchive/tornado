/**
 * Bootstrap for the SPA side of tornado
 * (Workboot/worksheet/chart management)
 *
 * @description RequireJS config
 */
require([
    '/assets/javascript/requireconfig.js'
], function () {
    require([
        'router'
    ], function(router) {
        router.start();
    });
});
