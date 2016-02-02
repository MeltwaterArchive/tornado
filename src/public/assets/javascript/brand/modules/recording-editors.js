/**
 * Recording editors (CSDL & VQB) initialization
 */
require(['jquery', 'buzzkill'], function($, Buzzkill) {
    'use strict';

    var EditorInit = function() {
        this.el = {
            form: '.single-item',
            recordingNameInput: '#recording-name',
            tab: '[data-recording-editor-tab]',
            submit: '.single-item__action--create',
            editor: {
                csdl: '[data-recording-editor-csdl',
                vqb: '[data-recording-editor-vqb]'
            }
        };

        this.classes = {
            tabActive: 'recording__editor-tab--active'
        };

        this.attributes = {
            tab: 'data-recording-editor-tab'
        };

        this.submitUrl = $(this.el.form).attr('action');
        this.csdlEditor = {};
        this.vqbEditor = {};

        this.initialize();
    };

    EditorInit.prototype.initialize = function() {
        // Initialize the CSDL Editor
        require(['csdleditor'], function(CSDLEditor) {
            this.csdlEditor = new CSDLEditor.Editor($(this.el.editor.csdl), {
                config: {
                    targets: CSDLEditorTargets || []
                }
            });

            // Hack to make the csdl editor display current value
            setTimeout(function() {
                this.csdlEditor.codeMirror.refresh();
            }.bind(this), 10);
        }.bind(this));

        // Initialize the VQB Editor
        require(['vqbeditor'], function(VQBEditor) {
            this.vqbEditor = new VQBEditor.GUI($(this.el.editor.vqb), {
                saveButton: false,
                cancelButton: false
            });
        }.bind(this));

        this.bindEvents();
    };

    EditorInit.prototype.bindEvents = function() {
        var _this = this;

        $('body').on('click', this.el.tab, function(ev) {
            $(_this.el.tab).removeClass(_this.classes.tabActive);
            $(this).addClass(_this.classes.tabActive);
        });

        $('body').on('click', this.el.submit, function(ev) {
            ev.preventDefault();

            _this.processForm();

            return false;
        });
    };

    EditorInit.prototype.getActiveEditor = function() {
        return $('.' + this.classes.tabActive).attr(this.attributes.tab);
    };

    EditorInit.prototype.processForm = function() {
        Buzzkill.clearForm($(this.el.form));

        var recordingName = $(this.el.recordingNameInput).val();

        // Don't submit the form when we haven't filled in everything
        if (recordingName === '') {
            Buzzkill.form($(this.el.form), {
                name: 'Name cannot be empty.'
            });

            return false;
        }

        var editorName = this.getActiveEditor();

        switch (editorName) {
            case 'csdl':
                var value = this.csdlEditor.value();

                if (value === '') {
                    Buzzkill.form($(this.el.form), {
                        csdl: ''
                    });

                    return false;
                };

                this.doSubmit({
                    name: recordingName,
                    csdl: this.csdlEditor.value(),
                    vqb_generated: false
                });

                break;
            case 'vqb':
                this.doSubmit({
                    name: recordingName,
                    csdl: this.vqbEditor.returnJCSDL(),
                    vqb_generated: true
                });

                break;
        }
    };

    EditorInit.prototype.doSubmit = function(data) {
        $.ajax ({
            url: this.submitUrl,
            type: 'POST',
            data: JSON.stringify(data),
            dataType: 'json',
            contentType: 'application/json',
            success: function(response) {
                window.location.href = response.meta.redirect_uri;
            },
            error: function(xhr) {
                var response = xhr.responseJSON;
                if (response.meta.csdl) {
                    Buzzkill.form($(this.el.form), {
                        csdl: response.meta.csdl
                    });
                }
            }.bind(this)
        });
    };

    return new EditorInit();
});
