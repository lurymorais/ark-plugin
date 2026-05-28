{**
 * plugins/pubIds/ark/templates/arkSuffixEdit.tpl
 *
 * ARK suffix edit template for pub identifiers form
 *}

{fbvFormSection title="plugins.pubIds.ark.displayName"}
    {fbvElement type="text" id="arkSuffix" label="plugins.pubIds.ark.manager.settings.arkSuffix" value=$arkSuffix size=$fbvStyles.size.MEDIUM}
    <p class="pkp_help">{translate key="plugins.pubIds.ark.editor.arkHelp" prefix=$arkPrefix}</p>
{/fbvFormSection}