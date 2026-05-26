/**
 * @file plugins/pubIds/ark/js/ARKSettingsFormHandler.js
 *
 * Copyright (c) 2026 Lury Morais
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ARKSettingsFormHandler.js
 * @ingroup plugins_pubIds_ark_js
 *
 * @brief Handle the ARK Settings form
 */
(function($) {

    $.pkp.plugins.pubIds.ark = $.pkp.plugins.pubIds.ark || { js: { } };

    $.pkp.plugins.pubIds.ark.js.ARKSettingsFormHandler = function($form, options) {
        this.parent($form, options);
        
    };
    
    $.pkp.classes.Helper.inherits(
        $.pkp.plugins.pubIds.ark.js.ARKSettingsFormHandler,
        $.pkp.controllers.form.AjaxFormHandler
    );

})(jQuery);