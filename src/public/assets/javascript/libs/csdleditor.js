/* this is a require.js wrapper around CSDLEditor configured for Tornado App */
define(['libs/csdl-editor/csdleditor.min', 'libs/csdl-editor/csdleditor.config'],
function(editor, config) {
    'use strict';

    // reset the standard targets, as we definetely will not use them with Pylon
    window.CSDLEditorConfig.targets = [];

    return window.CSDLEditor;
});
