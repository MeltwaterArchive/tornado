/**
 * Recording editors (CSDL & VQB) initialization
 */
require(['jquery', 'buzzkill', 'selectize'], function($, Buzzkill, Selectize) {
    'use strict';

    var EditorInit = function() {
        this.el = {
            form: '.single-item',
            submit: '.single-item__action--create',
            filterInput: '#dataset-filter',
            editor: '[data-recording-editor-csdl]',
            recordingInput: '#dataset-recordingId'
        };

        this.csdlEditor = {};

        this.initialize();
    };

    EditorInit.prototype.initialize = function() {
        // Initialize the CSDL Editor
        require(['csdleditor'], function(CSDLEditor) {
            this.csdlEditor = new CSDLEditor.Editor($(this.el.editor), {
                value: $(this.el.filterInput).val(),
                config: {
                    targets: CSDLEditorTargets || []
                }
            });

            // Hack to make the csdl editor display current value
            setTimeout(function() {
                this.csdlEditor.codeMirror.refresh();
            }.bind(this), 10);
        }.bind(this));

        $('select').selectize();

        this.bindEvents();
    };

    EditorInit.prototype.bindEvents = function() {
        var _this = this;

        $('body').on('click', this.el.submit, function(ev) {

            _this.processForm();
        });
    };

    EditorInit.prototype.processForm = function() {
        Buzzkill.clearForm($(this.el.form));

        var filterInput = $(this.el.filterInput);
        filterInput.val(this.csdlEditor.value());
    };

    return new EditorInit();
});
