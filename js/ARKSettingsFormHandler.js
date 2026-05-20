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
        
        // Adiciona listener para sucesso do AJAX
        $form.on('formValid', function() {
            // Dispara notificação de sucesso
            var notificationId = $.pkp.classes.notification.NotificationHelper.generateElementId();
            var notificationHtml = '<div id="' + notificationId + '" class="pkp_notification notice success" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 200px; background: #eaf7ea; border-left: 4px solid #2b6e2b; padding: 12px 20px; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">' +
                '<span style="color: #2b6e2b;">✓ ' + $.pkp.locale.localize('common.changesSaved') + '</span>' +
                '<button type="button" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer; color: #2b6e2b;" onclick="$(this).parent().fadeOut();">×</button>' +
                '</div>';
            
            $('body').append(notificationHtml);
            
            // Auto-fade após 3 segundos
            setTimeout(function() {
                $('#' + notificationId).fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        });
    };
    
    $.pkp.classes.Helper.inherits(
        $.pkp.plugins.pubIds.ark.js.ARKSettingsFormHandler,
        $.pkp.controllers.form.AjaxFormHandler
    );

})(jQuery);
