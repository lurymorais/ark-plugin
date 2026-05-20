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
        
        // CORREÇÃO: Remove aria-hidden de elementos que podem receber foco
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
                <input type="text" id="arkCustomPrefix" name="arkCustomPrefix" value="{$arkCustomPrefix|escape}" maxlength="6" style="width: 70px; text-transform: uppercase;">
                <span class="instruct">{translate key="plugins.pubIds.ark.manager.settings.arkCustomPrefix.validation"}</span>
            </div>
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

    {literal}
    <script>
    (function() {
        var updateInterval = null;
        
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
        
        function updatePreview() {
            var prefixField = document.querySelector('input[name="arkPrefix"]');
            var resolverField = document.getElementById('arkResolver');
            var radioN2T = document.getElementById('resolverType_n2t');
            var radioCustom = document.getElementById('resolverType_custom');
            var customPrefixField = document.getElementById('arkCustomPrefix');
            
            if (!prefixField) {
                console.log('updatePreview: prefixField não encontrado');
                return;
            }
            
            var prefix = prefixField.value.trim();
            if (!prefix) {
                prefix = 'ark:12345';
            }
            
            // Determina qual resolvedor usar
            var resolver = '';
            if (radioCustom && radioCustom.checked) {
                resolver = resolverField ? resolverField.value.trim() : '';
            } else {
                resolver = 'https://n2t.net/';
            }
            
            // Obtém o valor do customPrefix com fallback
            var customPrefix = 'CRL'; // valor padrão
            if (customPrefixField) {
                customPrefix = customPrefixField.value.trim().toUpperCase();
                if (customPrefix.length < 2) customPrefix = 'CRL';
                if (customPrefix.length > 6) customPrefix = customPrefix.substring(0, 6);
            }
            
            // Gera sufixo
            var numbers = '23456789';
            var letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            var xxxx = '';
            var yyyy = '';
            for (var i = 0; i < 4; i++) {
                xxxx += numbers.charAt(Math.floor(Math.random() * numbers.length));
                yyyy += letters.charAt(Math.floor(Math.random() * letters.length));
            }
            var suffix = customPrefix + xxxx + '-' + yyyy;
            
            var fullArk = prefix.replace(/\/$/, '') + '/' + suffix;
            
            var previewElement = document.getElementById('arkPreviewExample');
            var urlElement = document.getElementById('arkPreviewUrl');
            
            if (previewElement) {
                previewElement.innerHTML = '<strong>' + fullArk + '</strong>';
            }
            
            if (urlElement && resolver) {
                var resolveUrl = resolver.replace(/\/$/, '') + '/' + fullArk;
                urlElement.innerHTML = '<a href="' + resolveUrl + '" target="_blank" style="color: #d00a6c;">' + resolveUrl + '</a>';
            } else if (urlElement) {
                urlElement.innerHTML = 'Informe a URL do resolvedor';
            }
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
                updatePreview();
            }
            
            if (radioN2T) radioN2T.addEventListener('change', toggleResolverField);
            if (radioCustom) radioCustom.addEventListener('change', toggleResolverField);
            toggleResolverField();
        }
        
        function setupListeners() {
            var prefixField = document.querySelector('input[name="arkPrefix"]');
            var customPrefixField = document.getElementById('arkCustomPrefix');
            var resolverInput = document.getElementById('arkResolver');
            
            if (!prefixField) {
                console.log('setupListeners: prefixField não encontrado');
                return false;
            }
            
            prefixField.addEventListener('input', updatePreview);
            prefixField.addEventListener('change', updatePreview);
            
            if (customPrefixField) {
                customPrefixField.addEventListener('input', updatePreview);
                customPrefixField.addEventListener('change', updatePreview);
            }
            
            if (resolverInput) {
                resolverInput.addEventListener('input', updatePreview);
                resolverInput.addEventListener('change', updatePreview);
            }
            
            updatePreview();
            return true;
        }
        
        // Aguarda o DOM carregar
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
