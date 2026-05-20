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
    {rdelim});
</script>

<form class="pkp_form" id="arkSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="pubIds" plugin=$pluginName verb="save"}">
    {csrf}
    {include file="common/formErrors.tpl"}

    {fbvFormArea id="arkObjectsFormArea" title="plugins.pubIds.ark.manager.settings.arkObjects"}
        <p class="pkp_help">{translate key="plugins.pubIds.ark.manager.settings.explainARKs"}</p>
        {fbvFormSection list="true"}
            {fbvElement type="checkbox" label="plugins.pubIds.ark.manager.settings.enablePublicationARK" id="enablePublicationARK" name="enablePublicationARK" checked=$enablePublicationARK|compare:true}
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
                <input type="text" id="arkCustomPrefix" name="arkCustomPrefix" value="{$arkCustomPrefix|escape}" maxlength="6" style="width: 70px; margin-left: 10px; text-transform: uppercase;">
                <span class="instruct">{translate key="plugins.pubIds.ark.manager.settings.arkCustomPrefix.validation"}</span>
            </div>
        {/fbvFormSection}
    {/fbvFormArea}

    {fbvFormArea id="arkResolverFormArea" title="plugins.pubIds.ark.manager.settings.arkResolver"}
        {fbvFormSection}
            <p class="pkp_help">{translate key="plugins.pubIds.ark.manager.settings.arkResolver.description"}</p>
            {fbvElement type="text" id="arkResolver" name="arkResolver" value=$arkResolver required="true" label="plugins.pubIds.ark.manager.settings.arkResolver" size=$fbvStyles.size.LARGE}
        {/fbvFormSection}
    {/fbvFormArea}

    {fbvFormArea id="arkPreviewArea" title="plugins.pubIds.ark.manager.settings.preview"}
        <div id="arkPreviewBox" style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; border-left: 4px solid #d00a6c;">
            <p style="margin: 0 0 5px 0; font-weight: bold; color: #333;">{translate key="plugins.pubIds.ark.manager.settings.previewLabel"}</p>
            <p id="arkPreviewExample" style="margin: 0; font-family: monospace; font-size: 16px; color: #d00a6c; word-break: break-all;">---</p>
            <p id="arkPreviewResolved" style="margin: 10px 0 0 0; font-size: 12px; color: #666;">
                <span id="arkPreviewUrl">---</span>
            </p>
        </div>
    {/fbvFormArea}

    {literal}
    <script>
    (function() {
        var updateInterval = null;
        
        function generateRandomSuffix(customPrefix) {
            var cp = customPrefix.trim().toUpperCase();
            if (cp.length < 2) cp = 'ABC';
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
        
        function updatePreview() {
            var prefixField = document.querySelector('input[name="arkPrefix"]');
            var resolverField = document.querySelector('input[name="arkResolver"]');
            var customPrefixField = document.getElementById('arkCustomPrefix');
            var radioRandom = document.getElementById('arkSuffixRandom');
            
            if (!prefixField) return;
            
            var prefix = prefixField.value.trim();
            var resolver = resolverField ? resolverField.value.trim() : '';
            var isRandom = radioRandom && radioRandom.checked;
            
            if (!prefix) {
                prefix = 'ark:12345';
            }
            
            var suffix = '';
            if (isRandom && customPrefixField) {
                suffix = generateRandomSuffix(customPrefixField.value);
            } else {
                suffix = 'exemplo-sufixo';
            }
            
            var previewElement = document.getElementById('arkPreviewExample');
            var urlElement = document.getElementById('arkPreviewUrl');
            
            if (previewElement) {
                if (suffix && suffix !== 'exemplo-sufixo') {
                    var fullArk = prefix.replace(/\/$/, '') + '/' + suffix;
                    
                    previewElement.style.transition = 'opacity 0.2s ease';
                    previewElement.style.opacity = '0.5';
                    setTimeout(function() {
                        previewElement.innerHTML = '<strong>' + fullArk + '</strong>';
                        previewElement.style.opacity = '1';
                    }, 100);
                    
                    if (resolver && urlElement) {
                        var resolveUrl = resolver.replace(/\/$/, '') + '/' + fullArk;
                        urlElement.style.transition = 'opacity 0.2s ease';
                        urlElement.style.opacity = '0.5';
                        setTimeout(function() {
                            urlElement.innerHTML = '<a href="' + resolveUrl + '" target="_blank" style="color: #d00a6c;">' + resolveUrl + '</a>';
                            urlElement.style.opacity = '1';
                        }, 100);
                    } else if (urlElement) {
                        urlElement.innerHTML = 'Informe a URL do resolvedor';
                    }
                } else if (prefix) {
                    previewElement.innerHTML = '<span style="color: #999;">Aguardando configuração do sufixo...</span>';
                } else {
                    previewElement.innerHTML = '<span style="color: #999;">Informe o prefixo ARK</span>';
                }
            }
        }        
        
        function startSuffixRotation() {
            if (updateInterval) clearInterval(updateInterval);
            
            updateInterval = setInterval(function() {
                var radioRandom = document.getElementById('arkSuffixRandom');
                var customPrefixField = document.getElementById('arkCustomPrefix');
                var prefixField = document.querySelector('input[name="arkPrefix"]');
                
                if (radioRandom && radioRandom.checked && customPrefixField) {
                    var previewElement = document.getElementById('arkPreviewExample');
                    if (previewElement) {
                        var newSuffix = generateRandomSuffix(customPrefixField.value);
                        var prefix = (prefixField && prefixField.value.trim()) ? prefixField.value.trim() : 'ark:12345';
                        var fullArk = prefix.replace(/\/$/, '') + '/' + newSuffix;
                        
                        previewElement.style.transition = 'opacity 0.3s ease';
                        previewElement.style.opacity = '0.3';
                        
                        setTimeout(function() {
                            previewElement.innerHTML = '<strong>' + fullArk + '</strong>';
                            previewElement.style.opacity = '1';
                        }, 150);
                        
                        var resolverField = document.querySelector('input[name="arkResolver"]');
                        var urlElement = document.getElementById('arkPreviewUrl');
                        if (resolverField && urlElement) {
                            var resolver = resolverField.value.trim();
                            if (resolver) {
                                var resolveUrl = resolver.replace(/\/$/, '') + '/' + fullArk;
                                urlElement.style.transition = 'opacity 0.3s ease';
                                urlElement.style.opacity = '0.3';
                                setTimeout(function() {
                                    urlElement.innerHTML = '<a href="' + resolveUrl + '" target="_blank" style="color: #d00a6c;">' + resolveUrl + '</a>';
                                    urlElement.style.opacity = '1';
                                }, 150);
                            }
                        }
                    }
                }
            }, 3000);
        }
        
        function setupListeners() {
            var prefixField = document.querySelector('input[name="arkPrefix"]');
            var resolverField = document.querySelector('input[name="arkResolver"]');
            var customPrefixField = document.getElementById('arkCustomPrefix');
            var radioRandom = document.getElementById('arkSuffixRandom');
            
            if (!prefixField) return false;
            
            prefixField.addEventListener('input', updatePreview);
            prefixField.addEventListener('change', updatePreview);
            if (resolverField) {
                resolverField.addEventListener('input', updatePreview);
                resolverField.addEventListener('change', updatePreview);
            }
            
            if (customPrefixField) {
                customPrefixField.addEventListener('input', function() {
                    updatePreview();
                    startSuffixRotation();
                });
            }
            
            if (radioRandom) {
                radioRandom.addEventListener('change', function() {
                    updatePreview();
                    if (radioRandom.checked) {
                        startSuffixRotation();
                    } else {
                        if (updateInterval) clearInterval(updateInterval);
                    }
                });
            }
            
            updatePreview();
            startSuffixRotation();
            
            return true;
        }
        
        if (setupListeners()) {
            // listeners ativos
        } else {
            var observer = new MutationObserver(function() {
                if (document.querySelector('input[name="arkPrefix"]')) {
                    setupListeners();
                    observer.disconnect();
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    })();
    </script>
    {/literal}

    {fbvFormButtons submitText="common.save"}
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
