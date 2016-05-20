/**
 * Bootstrap for the SPA side of tornado
 * (Workboot/worksheet/chart management)
 *
 * @description RequireJS config
 */
require([
    'requireconfig'
], function () {
    require([
        'router'
    ], function(router) {
        router.start();
    });
});
