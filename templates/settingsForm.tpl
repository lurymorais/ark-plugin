{**
 * plugins/pubIds/ark/templates/settingsForm.tpl
 *
 * Copyright (c) 2026 Lury Morais
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *}

<div id="description">{translate key="plugins.pubIds.ark.manager.settings.description"}</div>

<script src="{$baseUrl}/plugins/pubIds/ark/js/ARKSettingsFormHandler.js"></script>
<script>
    $(function() {ldelim}
        $('#arkSettingsForm').pkpHandler('$.pkp.plugins.pubIds.ark.js.ARKSettingsFormHandler');
        
        // Fix: Remove aria-hidden from focusable elements
        function fixAriaHidden() {
            $('[aria-hidden="true"]').each(function() {
                var $el = $(this);
                if ($el.is('a, button, input, select, textarea') || $el.find('a, button, input, select, textarea').length) {
                    $el.removeAttr('aria-hidden');
                }
            });
        }
        
        fixAriaHidden();
        
        var observer = new MutationObserver(function(mutations) {
            fixAriaHidden();
        });
        observer.observe(document.body, { childList: true, subtree: true });
    {rdelim});
</script>

<form class="pkp_form" id="arkSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="pubIds" plugin=$pluginName verb="save"}">
    {csrf}
    {include file="common/formErrors.tpl"}

    {fbvFormArea id="arkObjectsFormArea" title="plugins.pubIds.ark.manager.settings.arkObjects"}
        <p class="pkp_help">{translate key="plugins.pubIds.ark.manager.settings.explainARKs"}</p>
        {fbvFormSection list="true"}
            {fbvElement type="checkbox" label="plugins.pubIds.ark.manager.settings.enablePublicationARK" id="enablePublicationARK" name="enablePublicationARK" checked=$enablePublicationARK}
            {fbvElement type="checkbox" label="plugins.pubIds.ark.manager.settings.enableIssueARK" id="enableIssueARK" name="enableIssueARK" checked=$enableIssueARK}
        {/fbvFormSection}
    {/fbvFormArea}

    {fbvFormArea id="arkPrefixFormArea" title="plugins.pubIds.ark.manager.settings.arkPrefix"}
        {fbvFormSection}
            <p class="pkp_help">{translate key="plugins.pubIds.ark.manager.settings.arkPrefix.description"}</p>
            {fbvElement type="text" id="arkPrefix" name="arkPrefix" value=$arkPrefix required="true" label="plugins.pubIds.ark.manager.settings.arkPrefix" maxlength="40" size=$fbvStyles.size.MEDIUM}
        {/fbvFormSection}
    {/fbvFormArea}

    {fbvFormArea id="arkSuffixFormArea" title="plugins.pubIds.ark.manager.settings.arkSuffix"}
        <p class="pkp_help">{translate key="plugins.pubIds.ark.manager.settings.arkSuffix.description"}</p>

        {fbvFormSection list="true"}
            <div>
                <input type="radio" id="arkSuffixRandom" name="arkSuffix" value="random" {if $arkSuffix == 'random'}checked="checked"{/if}>
                <label for="arkSuffixRandom">{translate key="plugins.pubIds.ark.manager.settings.arkSuffixRandom"}</label>
            </div>
            <div style="margin-left: 25px; margin-top: 5px; margin-bottom: 10px;">
                <label for="arkCustomPrefix">{translate key="plugins.pubIds.ark.manager.settings.arkCustomPrefix"}</label>
                <input type="text" id="arkCustomPrefix" name="arkCustomPrefix" value="{$arkCustomPrefix|escape}" maxlength="6" style="width: 70px; text-transform: uppercase;">
                <span class="instruct">{translate key="plugins.pubIds.ark.manager.settings.arkCustomPrefix.validation"}</span>
            </div>
        {/fbvFormSection}
    {/fbvFormArea}

    {fbvFormArea id="arkImplementationDateArea" title="plugins.pubIds.ark.manager.settings.arkImplementationDate"}
        {fbvFormSection}
            <p class="pkp_help">{translate key="plugins.pubIds.ark.manager.settings.arkImplementationDate.description"}</p>
            <p style="border-left: 4px solid rgb(208, 10, 108); padding: 10px; margin: 10px 0; border-radius: 4px;">
                <strong>{translate key="plugins.pubIds.ark.manager.settings.arkImplementationDate.recommendation"}</strong>
            </p>
            <input type="text" 
                   id="arkImplementationDate" 
                   name="arkImplementationDate" 
                   value="{$arkImplementationDate|escape}" 
                   maxlength="8" 
                   style="width: 150px; padding: 6px; border: 1px solid #ddd; border-radius: 4px;"
                   placeholder="YYYYMMDD">
            <span class="instruct">{translate key="plugins.pubIds.ark.manager.settings.form.arkImplementationDatePattern"}</span>
        {/fbvFormSection}
    {/fbvFormArea}

    {fbvFormArea id="arkResolverFormArea" title="plugins.pubIds.ark.manager.settings.arkResolver"}
        {fbvFormSection}
            <p class="pkp_help">{translate key="plugins.pubIds.ark.manager.settings.arkResolver.description"}</p>
            
            <div style="margin: 15px 0; padding: 10px; background: #f9f9f9; border-radius: 5px;">
                <div style="margin-bottom: 10px;">
                    <label style="font-weight: normal;">
                        <input type="radio" id="resolverType_n2t" name="resolverType" value="n2t" {if $resolverType != 'custom'}checked="checked"{/if}>
                        <span style="font-weight: normal;">{translate key="plugins.pubIds.ark.manager.settings.arkResolver.n2t"}</span>
                    </label>
                    <div style="margin-left: 25px; font-size: 12px; color: #666;">
                        https://n2t.net/
                    </div>
                </div>
                
                <div>
                    <label style="font-weight: normal;">
                        <input type="radio" id="resolverType_custom" name="resolverType" value="custom" {if $resolverType == 'custom'}checked="checked"{/if}>
                        <span style="font-weight: normal;">{translate key="plugins.pubIds.ark.manager.settings.arkResolver.custom"}</span>
                    </label>
                    <div class="resolver-custom-field" style="margin-left: 25px; margin-top: 8px;">
                        <input type="text" id="arkResolver" name="arkResolver" value="{$arkResolver|escape}" style="width: 100%;" placeholder="https://site.com/resolver" {if $resolverType != 'custom'}disabled{/if}>
                        <label for="arkResolver" style="font-size: 12px; color: #666;">{translate key="plugins.pubIds.ark.manager.settings.arkResolver.customUrl"}</label>
                    </div>
                </div>
            </div>
        {/fbvFormSection}
    {/fbvFormArea}

    {fbvFormArea id="telemetryArea" title="plugins.pubIds.ark.settings.telemetryLevel"}
        {fbvFormSection}
            <p class="pkp_help">{translate key="plugins.pubIds.ark.manager.settings.telemetryLevel.description"}</p>
            
            <div style="margin: 15px 0; padding: 10px; background: #f9f9f9; border-radius: 5px;">
                <div style="margin-bottom: 10px;">
                    <label style="font-weight: normal;">
                        <input type="radio" name="telemetryLevel" value="restricted" {if $telemetryLevel != 'public'}checked="checked"{/if}>
                        <span style="font-weight: bold;">{translate key="plugins.pubIds.ark.settings.telemetryLevel.restricted"}</span>
                    </label>
                    <div style="margin-left: 25px; font-size: 12px; color: #666;">
                        {translate key="plugins.pubIds.ark.manager.settings.telemetryLevel.restricted.description"}
                    </div>
                </div>
                
                <div>
                    <label style="font-weight: normal;">
                        <input type="radio" name="telemetryLevel" value="public" {if $telemetryLevel == 'public'}checked="checked"{/if}>
                        <span style="font-weight: bold;">{translate key="plugins.pubIds.ark.settings.telemetryLevel.public"}</span>
                    </label>
                    <div style="margin-left: 25px; font-size: 12px; color: #666;">
                        {translate key="plugins.pubIds.ark.manager.settings.telemetryLevel.public.description"}
                    </div>
                </div>
            </div>
            
            <div class="pkp_helpers_alert pkp_helpers_alert--info" style="margin-top: 10px;">
                {translate key="plugins.pubIds.ark.manager.settings.telemetryInfo"}
            </div>
        {/fbvFormSection}
    {/fbvFormArea}

    {fbvFormArea id="tokenRecoveryArea" title="plugins.pubIds.ark.recovery.title"}
        {fbvFormSection}
            <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 20px; margin: 15px 0;">
                <h4 style="color: #856404; margin-top: 0; display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 20px;">🔒</span> 
                    {translate key="plugins.pubIds.ark.recovery.securityArea"}
                </h4>
                
                <p style="color: #856404; margin-bottom: 15px;">
                    {translate key="plugins.pubIds.ark.recovery.securityNote"}
                </p>
                
                <hr style="border-color: #ffeeba; margin: 15px 0;">
                
                <p><strong>{translate key="plugins.pubIds.ark.recovery.howItWorks"}</strong></p>
                <ol style="margin: 10px 0 15px 20px; color: #333;">
                    <li>{translate key="plugins.pubIds.ark.recovery.step1"}</li>
                    <li>{translate key="plugins.pubIds.ark.recovery.step2"}</li>
                    <li>{translate key="plugins.pubIds.ark.recovery.step3"}</li>
                    <li>{translate key="plugins.pubIds.ark.recovery.step4"}</li>
                </ol>
                
                <div style="background: #e8f4f8; border-left: 4px solid #006798; padding: 12px; margin: 15px 0; border-radius: 4px;">
                    <p style="margin: 0; font-size: 13px;">
                        <strong>{translate key="plugins.pubIds.ark.recovery.targetHintLabel"}</strong><br>
                        <code style="word-break: break-all;">{$baseUrl}/plugins/pubIds/ark/resolver.php?ark={literal}${value}{/literal}</code>
                    </p>
                </div>
                
                <button type="button" id="recoveryButton" class="pkp_button">
                    {translate key="plugins.pubIds.ark.recovery.button"}
                </button>
            </div>
        {/fbvFormSection}
    {/fbvFormArea}

    {literal}

    <script>
    // Force telemetryLevel value to be sent in the form
    $(document).ready(function() {
        // Make sure the default value is sent
        $('form#arkSettingsForm').on('submit', function() {
            var selectedLevel = $('input[name="telemetryLevel"]:checked').val();
            if (!selectedLevel) {
                $('input[name="telemetryLevel"][value="restricted"]').prop('checked', true);
            }
        });
    });
    </script>

    <script>
        var lastRecoveryAttempt = 0;
        var RECOVERY_COOLDOWN = 3600000;

        $(document).ready(function() {
            // Load timestamp of last server attempt
            $.ajax({
                url: 'https://revistacarnaubais.com.br/ark-telemetry/recovery.php?check=1',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.lastAttempt && data.canAttempt === false) {
                        var remaining = data.remainingMinutes;
                        $('#recoveryButton').prop('disabled', true);
                        $('#recoveryButton').text('Aguarde ' + remaining + ' minutos');
                    }
                }
            });
            
            $('#recoveryButton').on('click', function() {
                var $btn = $(this);
                var now = Date.now();
                
                // Check local rate limit
                if (lastRecoveryAttempt > 0 && (now - lastRecoveryAttempt) < RECOVERY_COOLDOWN) {
                    var remaining = Math.ceil((RECOVERY_COOLDOWN - (now - lastRecoveryAttempt)) / 60000);
                    alert('Aguarde ' + remaining + ' minutos antes de tentar novamente.');
                    return;
                }
                
                var originalText = $btn.text();
                $btn.text('Verificando...').prop('disabled', true);
                lastRecoveryAttempt = now;
                
                $.ajax({
                    url: 'https://revistacarnaubais.com.br/ark-telemetry/recovery.php',
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        $btn.text(originalText).prop('disabled', false);
                        alert(response.content || response.message || 'Verificação concluída');
                        
                        if (response.status === true) {
                            location.reload();
                        }
                    },
                    error: function(xhr) {
                        $btn.text(originalText).prop('disabled', false);
                        var errorMsg = xhr.responseJSON?.content || 'Erro na verificação. Tente novamente.';
                        alert(errorMsg);
                    }
                });
            });
        });
    </script>

    <script>
    (function() {
        var updateInterval = null;
        var ROTATION_INTERVAL = 5000;
        
        function generateRandomSuffix(customPrefix) {
            var cp = customPrefix.trim().toUpperCase();
            if (cp.length < 2) cp = 'CRL';
            if (cp.length > 6) cp = cp.substring(0, 6);
            
            var numbers = '23456789';
            var letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            var xxxx = '';
            var yyyy = '';
            for (var i = 0; i < 4; i++) {
                xxxx += numbers.charAt(Math.floor(Math.random() * numbers.length));
                yyyy += letters.charAt(Math.floor(Math.random() * letters.length));
            }
            return cp + xxxx + '-' + yyyy;
        }
        
        function updatePreview(withTransition) {
            var prefixField = document.querySelector('input[name="arkPrefix"]');
            var resolverField = document.getElementById('arkResolver');
            var radioN2T = document.getElementById('resolverType_n2t');
            var radioCustom = document.getElementById('resolverType_custom');
            var customPrefixField = document.getElementById('arkCustomPrefix');
            
            if (!prefixField) {
                return;
            }
            
            var prefix = prefixField.value.trim();
            if (!prefix) {
                prefix = 'ark:12345';
            }
            
            var resolver = '';
            if (radioCustom && radioCustom.checked) {
                resolver = resolverField ? resolverField.value.trim() : '';
            } else {
                resolver = 'https://n2t.net/';
            }
            
            var customPrefix = 'CRL';
            if (customPrefixField) {
                customPrefix = customPrefixField.value.trim().toUpperCase();
                if (customPrefix.length < 2) customPrefix = 'CRL';
                if (customPrefix.length > 6) customPrefix = customPrefix.substring(0, 6);
            }
            
            var suffix = generateRandomSuffix(customPrefix);
            var fullArk = prefix.replace(/\/$/, '') + '/' + suffix;
            
            var previewElement = document.getElementById('arkPreviewExample');
            var urlElement = document.getElementById('arkPreviewUrl');
            
            function applyTransition(element, callback) {
                if (withTransition !== false) {
                    element.style.transition = 'opacity 0.3s ease';
                    element.style.opacity = '0.3';
                    setTimeout(function() {
                        callback();
                        element.style.opacity = '1';
                    }, 100);
                } else {
                    callback();
                }
            }
            
            if (previewElement) {
                applyTransition(previewElement, function() {
                    previewElement.innerHTML = '<strong>' + fullArk + '</strong>';
                });
            }
            
            if (urlElement && resolver) {
                var resolveUrl = resolver.replace(/\/$/, '') + '/' + fullArk;
                applyTransition(urlElement, function() {
                    urlElement.innerHTML = '<a href="' + resolveUrl + '" target="_blank" style="color: #d00a6c;">' + resolveUrl + '</a>';
                });
            } else if (urlElement) {
                applyTransition(urlElement, function() {
                    urlElement.innerHTML = 'Enter resolver URL';
                });
            }
        }
        
        function updatePreviewWithTransition() {
            updatePreview(true);
        }
        
        function updatePreviewNoTransition() {
            updatePreview(false);
        }
        
        function startRotation() {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
            
            updateInterval = setInterval(function() {
                var radioRandom = document.getElementById('arkSuffixRandom');
                if (radioRandom && radioRandom.checked) {
                    updatePreviewWithTransition();
                }
            }, ROTATION_INTERVAL);
        }
        
        function setupResolvers() {
            var radioN2T = document.getElementById('resolverType_n2t');
            var radioCustom = document.getElementById('resolverType_custom');
            var resolverInput = document.getElementById('arkResolver');
            
            function toggleResolverField() {
                if (radioCustom && radioCustom.checked) {
                    if (resolverInput) {
                        resolverInput.disabled = false;
                        resolverInput.focus();
                    }
                } else {
                    if (resolverInput) {
                        resolverInput.disabled = true;
                        resolverInput.value = '';
                    }
                }
                updatePreviewWithTransition();
            }
            
            if (radioN2T) radioN2T.addEventListener('change', toggleResolverField);
            if (radioCustom) radioCustom.addEventListener('change', toggleResolverField);
            toggleResolverField();
        }
        
        function setupListeners() {
            var prefixField = document.querySelector('input[name="arkPrefix"]');
            var customPrefixField = document.getElementById('arkCustomPrefix');
            var resolverInput = document.getElementById('arkResolver');
            var radioRandom = document.getElementById('arkSuffixRandom');
            
            if (!prefixField) {
                return false;
            }
            
            prefixField.addEventListener('input', updatePreviewWithTransition);
            prefixField.addEventListener('change', updatePreviewWithTransition);
            
            if (customPrefixField) {
                customPrefixField.addEventListener('input', updatePreviewWithTransition);
                customPrefixField.addEventListener('change', updatePreviewWithTransition);
            }
            
            if (resolverInput) {
                resolverInput.addEventListener('input', updatePreviewWithTransition);
                resolverInput.addEventListener('change', updatePreviewWithTransition);
            }
            
            if (radioRandom) {
                radioRandom.addEventListener('change', function() {
                    updatePreviewWithTransition();
                    if (radioRandom.checked) {
                        startRotation();
                    } else {
                        if (updateInterval) {
                            clearInterval(updateInterval);
                            updateInterval = null;
                        }
                    }
                });
            }
            
            updatePreviewNoTransition();
            
            if (radioRandom && radioRandom.checked) {
                startRotation();
            }
            
            return true;
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setupResolvers();
                setupListeners();
            });
        } else {
            setupResolvers();
            setupListeners();
        }
    })();
    </script>

    <script>
        // Recovery button - with full diagnostics
        $(document).ready(function() {
            $('#recoveryButton').on('click', function() {
                var $btn = $(this);
                var originalText = $btn.text();
                $btn.text('Verificando...').prop('disabled', true);
                
                var recoveryUrl = 'https://revistacarnaubais.com.br/ark-telemetry/recovery.php';
                
                $.ajax({
                    url: recoveryUrl,
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        $btn.text(originalText).prop('disabled', false);
                        
                        // Display exact message returned by server
                        var msg = response.content || response.message || 'Resposta sem mensagem';
                        alert(msg);
                        
                        if (response.status === true) {
                            location.reload();
                        }
                    },
                    error: function(xhr) {
                        $btn.text(originalText).prop('disabled', false);
                        
                        // Try to extract real error message
                        var errorMsg = '';
                        
                        if (xhr.responseJSON) {
                            errorMsg = xhr.responseJSON.content || xhr.responseJSON.message || JSON.stringify(xhr.responseJSON);
                        } else if (xhr.responseText) {
                            errorMsg = xhr.responseText.substring(0, 500);
                        } else if (xhr.status === 0) {
                            errorMsg = 'Não foi possível conectar ao servidor.';
                        } else if (xhr.status === 404) {
                            errorMsg = 'Endpoint não encontrado: ' + recoveryUrl;
                        } else if (xhr.status === 500) {
                            errorMsg = 'Erro interno no servidor.';
                        } else {
                            errorMsg = 'Erro HTTP ' + xhr.status + ': ' + xhr.statusText;
                        }
                        
                        alert('Erro: ' + errorMsg);
                    }
                });
            });
        });
    </script>

    <script>
// NAAN validation before submitting the form
$(document).ready(function() {

    $('#arkSettingsForm').on('submit', function(e) {
        var naan = $('input[name="arkPrefix"]').val();
        
        // Make AJAX request to validate NAAN before sending
        $.ajax({
            url: 'https://revistacarnaubais.com.br/ark-telemetry/validate-naan.php',
            type: 'POST',
            data: { naan: naan, domain: window.location.hostname },
            dataType: 'json',
            async: false, // Wait for response
            success: function(response) {
                if (response.valid !== true) {
                    alert(response.message || 'Este NAAN não pertence ao seu domínio!');
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                }
            },
            error: function() {
                alert('Erro ao validar NAAN. Tente novamente.');
                e.preventDefault();
                return false;
            }
        });
    });
});
</script>

    {/literal}

    {fbvFormArea id="arkPreviewArea" title="plugins.pubIds.ark.manager.settings.preview"}
        <div id="arkPreviewBox" style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; border-left: 4px solid #d00a6c;">
            <p style="margin: 0 0 5px 0; font-weight: bold; color: #333;">{translate key="plugins.pubIds.ark.manager.settings.previewLabel"}</p>
            <p id="arkPreviewExample" style="margin: 0; font-family: monospace; font-size: 16px; color: #d00a6c; word-break: break-all;">---</p>
            <p id="arkPreviewResolved" style="margin: 10px 0 0 0; font-size: 12px; color: #666;">
                <span id="arkPreviewUrl">---</span>
            </p>
        </div>
    {/fbvFormArea}

    {fbvFormButtons submitText="common.save"}
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>