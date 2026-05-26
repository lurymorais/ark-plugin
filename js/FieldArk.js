/**
 * @file plugins/pubIds/ark/js/FieldArk.js
 *
 * Copyright (c) 2026 Lury Morais
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Gerador de ARKs no formato: PREFIXO/CRLxxxx-yyyy
 */

(function($) {
    function generateArkSuffix(customPrefix) {
        if (!customPrefix) customPrefix = 'CRL';
        customPrefix = customPrefix.toUpperCase();
        if (customPrefix.length < 2) customPrefix = 'ABC';
        if (customPrefix.length > 6) customPrefix = customPrefix.substring(0, 6);
        
        var suffix = customPrefix;
        
        var numbers = '23456789';
        for (var i = 0; i < 4; i++) {
            suffix += numbers.charAt(Math.floor(Math.random() * numbers.length));
        }
        
        suffix += '-';
        
        var letters = 'BCDFGHJKLMNPQRSTVWXYZ';
        for (var i = 0; i < 4; i++) {
            suffix += letters.charAt(Math.floor(Math.random() * letters.length));
        }
        
        return suffix;
    }

    function initArkInjector() {
        // Procura o campo de ARK (pode demorar para carregar devido ao Vue)
        var checkInterval = setInterval(function() {
            var $input = $('input[name="pub-id::ark"]');
            
            if ($input.length > 0) {
                // Verifica se já existe o botão
                if ($input.next('.ark-generate-btn').length === 0) {
                    
                    var generateLabel = 'Gerar ARK';
                    if (window.arkPluginConfig && window.arkPluginConfig.generateLabel) {
                        generateLabel = window.arkPluginConfig.generateLabel;
                    }
                    
                    var $btn = $('<button>')
                        .attr('type', 'button')
                        .addClass('pkpButton ark-generate-btn')
                        .text(generateLabel);

                    $btn.click(function(e) {
                        e.preventDefault();
                        
                        var prefix = '';
                        var customPrefix = 'CRL';
                        
                        if (window.arkPluginConfig) {
                            if (window.arkPluginConfig.prefix) {
                                prefix = window.arkPluginConfig.prefix;
                            }
                            if (window.arkPluginConfig.customPrefix) {
                                customPrefix = window.arkPluginConfig.customPrefix;
                            }
                        }
                        
                        prefix = String(prefix).replace(/\/$/, '');
                        var suffix = generateArkSuffix(customPrefix);
                        var fullArk = prefix + '/' + suffix;
                        
                        $input.val(fullArk);
                        $input.trigger('input');
                        $input.trigger('change');
                        
                        // Feedback visual
                        $btn.css('background-color', '#e0e0e0');
                        setTimeout(function() {
                            $btn.css('background-color', '#fff');
                        }, 200);
                    });

                    $input.after($btn);
                    $input.parent().addClass('ark-field-wrapper');
                    
                    // Se o campo já tem valor, pode ser útil mostrar
                    if ($input.val()) {
                        console.log('ARK existente:', $input.val());
                    }
                }
            }
        }, 500);
        
        // Para o intervalo após encontrar (opcional, para não ficar rodando)
        setTimeout(function() {
            clearInterval(checkInterval);
        }, 30000);
    }

    $(document).ready(function() {
        initArkInjector();
    });

})(jQuery);
