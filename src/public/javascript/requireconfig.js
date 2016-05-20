requirejs.config({

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
        'chosen': {
            deps: [
                'jquery'
            ],
            exports: 'chosen'
        },
        'd3': {
            exports: 'd3'
        }
    },
    hbs: {
        disableI18n: true,
        helpers: true,
        templateExtension: 'mst',
        partialsUrl: 'templates/',
        helperDirectory: 'templates/helpers/'
    },
    paths: {
        jquery: '/bower/jquery/dist/jquery.min',
        underscore: '/bower/lodash/lodash.min',
        backbone: '/bower/backbone/backbone-min',
        mustache: '/bower/mustache/mustache.min',
        d3: '/bower/d3/d3.min',
        text : '/bower/text/text',
        chosen: '/bower/chosen/chosen.jquery.min',
        moment: '/bower/moment/min/moment.min',
        bootstrap: '/bower/datasift-bootstrap/javascript',
        trace: '/bower/trace/trace-min',
        hbs: '/bower/require-handlebars-plugin/hbs',
        // older stuff
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