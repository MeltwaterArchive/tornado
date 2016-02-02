requirejs.config({
    baseUrl: '/assets/javascript/',

    shim: {
        'underscore': {
            exports: '_'
        },
        'backbone': {
            deps: [
                'underscore',
                'jquery'
            ],
            exports: 'Backbone'
        },
        'd3': {
            exports: 'd3'
        }
    },

    paths: {
        jquery: 'library/jquery/dist/jquery',
        // underscore is actually lodash - go figure?
        underscore: 'library/lodash/lodash',
        backbone: 'library/backbone/backbone-min',
        mustache: 'library/mustache/mustache.min',
        d3: 'library/d3/d3',
        text : 'library/text/text',
        moment: 'library/moment/moment',
        trace: 'libs/trace',
        csdleditor: 'libs/csdleditor',
        promise: 'libs/native-promise-only',
        rangeslider: 'libs/ion.rangeSlider/js/ion.rangeSlider.min',
        selectize: 'libs/selectize/selectize.min',
        selectide: 'plugins/selectide',
        draggaball: 'plugins/draggaball',
        spinner: 'plugins/spinner',
        blocker: 'plugins/blocker',
        loader: 'plugins/loader',
        buzzkill: 'plugins/buzzkill',
        modallica: 'plugins/modallica',
        contextmenu: 'plugins/context-menu',
        tooltip: 'plugins/tooltip',
        dropdownToggle: 'plugins/dropdown-toggle',
        vqbeditor: 'libs/vqbeditor',
        checkmate: 'plugins/checkmate',
        responseFormatter: 'services/http/error-formatter'
    }
});