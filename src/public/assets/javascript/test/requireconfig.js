/**
 * Require JS Config
 *
 * This is loaded in a seperate file from the main require JS otherwise 
 * phamtonJS will throw an error
 *
 * @see http://stackoverflow.com/questions/29435486/how-to-setup-grunt-task-for-requirejs-and-qunit
 * 
 * @type {Object}
 */
require = {
	baseUrl: '/assets/javascript/',
	paths: {
                jquery: 'library/jquery/dist/jquery',
                qunit: 'library/qunit/qunit/qunit',
                underscore: 'library/lodash/lodash',
                backbone: 'library/backbone/backbone-min',
                mustache: 'library/mustache/mustache.min',
                d3: 'libs/d3/d3',
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
                tooltip: 'plugins/tooltip'
	},
	shim: {
		'qunit': {
			exports: 'QUnit',
			init: function () {
				QUnit.config.autoload = false;
				QUnit.config.autostart = false;
			}
		}
	}
};
