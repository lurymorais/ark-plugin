/**
 * @file plugins/pubIds/ark/js/FieldArk.js
 * ARK Generator for Publications (Articles only)
 * 
 * Copyright (c) 2026 Lury Morais
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 */

(function($) {
    var saveUrl = null;
    var checkUrl = null;
    
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
    
    function getPublicationId() {
        var $input = $('input[name="publicationId"]');
        if ($input.length) return $input.val();
        
        var match = window.location.href.match(/\/publication\/(\d+)/);
        if (match) return match[1];
        
        match = window.location.href.match(/publicationId=(\d+)/);
        if (match) return match[1];
        
        return null;
    }
    
    function checkDuplicate(arkValue, publicationId, callback) {
        if (!checkUrl) {
            callback(false);
            return;
        }
        
        fetch(checkUrl + '?check_article=1&publicationId=' + publicationId + '&ark=' + encodeURIComponent(arkValue))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                callback(data.duplicate === true);
            })
            .catch(function() { callback(false); });
    }
    
    function showNotification(message, type) {
        var notification = $("<div>")
            .addClass("ark-notification ark-notification-" + type)
            .text(message)
            .css({
                "position": "fixed",
                "top": "20px",
                "right": "20px",
                "padding": "12px 20px",
                "background": type === "success" ? "#00b24e" : (type === "error" ? "#d00a0a" : "#006798"),
                "color": "white",
                "border-radius": "4px",
                "z-index": "9999",
                "box-shadow": "0 2px 10px rgba(0,0,0,0.2)",
                "font-size": "14px",
                "font-weight": "500",
                "max-width": "400px",
                "word-wrap": "break-word"
            });
        
        $("body").append(notification);
        
        setTimeout(function() {
            notification.fadeOut(500, function() { $(this).remove(); });
        }, 8000);
    }
    
    function initArkInjector() {
        if (window.location.href.indexOf('manageIssues') !== -1) {
            return;
        }
        
        if (window.location.href.indexOf('issueId=') !== -1 && window.location.href.indexOf('manageIssues') !== -1) {
            return;
        }
        
        saveUrl = window.arkPluginConfig ? window.arkPluginConfig.saveUrl : '/plugins/pubIds/ark/save_ajax.php';
        checkUrl = window.arkPluginConfig ? window.arkPluginConfig.checkUrl : '/plugins/pubIds/ark/save_ajax.php';
        
        var checkInterval = setInterval(function() {
            var $input = $('input[name="pub-id::ark"]');
            
            if ($input.length > 0) {
                // Fill existing ARK if available
                if (!$input.val()) {
                    var publicationId = getPublicationId();
                    if (publicationId) {
                        fetch(checkUrl + '?check_article=1&publicationId=' + publicationId + '&ark=')
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                if (data.ark && !data.duplicate) {
                                    $input.val(data.ark);
                                    $input.trigger('input');
                                    $input.trigger('change');
                                }
                            })
                            .catch(function() {});
                    }
                }
                
                if ($input.next('.ark-generate-btn').length === 0) {
                    var generateLabel = 'Gerar ARK';
                    if (window.arkPluginConfig && window.arkPluginConfig.generateLabel) {
                        generateLabel = window.arkPluginConfig.generateLabel;
                    }
                    
                    var $btn = $('<button>')
                        .attr('type', 'button')
                        .addClass('pkpButton ark-generate-btn')
                        .text(generateLabel)
                        .css({
                            'margin-left': '10px',
                            'background': '#fff',
                            'color': '#d00a6c',
                            'border': '1px solid #aaa',
                            'padding': '5px 15px',
                            'border-radius': '3px',
                            'cursor': 'pointer'
                        });
                    
                    var $form = $input.closest('form');
                    var originalSubmit = null;
                    
                    if ($form.length) {
                        originalSubmit = $form.get(0).submit;
                        $form.get(0).submit = function() {
                            var arkValue = $input.val();
                            var publicationId = $('input[name="publicationId"]').val();
                            
                            if (arkValue && publicationId) {
                                checkDuplicate(arkValue, publicationId, function(isDuplicate) {
                                    if (isDuplicate) {
                                        showNotification('ERROR: This ARK is already in use by another article or issue! Use the "Generate ARK" button to create a unique identifier.', 'error');
                                    } else {
                                        originalSubmit.call($form.get(0));
                                    }
                                });
                            } else {
                                originalSubmit.call($form.get(0));
                            }
                        };
                    }
                    
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
                        
                        // Trigger multiple events to ensure OJS detects the change
                        $input.trigger('focus');
                        $input.trigger('input');
                        $input.trigger('change');
                        $input.trigger('blur');
                        
                        // Native events for frameworks
                        var evt = new Event('input', { bubbles: true });
                        $input[0].dispatchEvent(evt);
                        
                        var evt2 = new Event('change', { bubbles: true });
                        $input[0].dispatchEvent(evt2);
                        
                        $input.trigger('keyup');
                        
                        $btn.css('opacity', '0.7');
                        setTimeout(function() { $btn.css('opacity', '1'); }, 200);
                    });
                    
                    $input.after($btn);
                    $input.css('flex', '1');
                    $input.parent().css('display', 'flex').css('align-items', 'center');
                }
            }
        }, 500);
        
        setTimeout(function() {
            clearInterval(checkInterval);
        }, 30000);
    }
    
    $(document).ready(function() {
        initArkInjector();
    });
})(jQuery);