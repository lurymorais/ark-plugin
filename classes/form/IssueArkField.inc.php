<?php
/**
 * @file plugins/pubIds/ark/classes/form/IssueArkField.inc.php
 *
 * Copyright (c) 2026 Lury Morais
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 */

namespace Plugins\PubIds\Ark\Classes\Form;

use PKP\components\forms\FieldText;

class IssueArkField extends FieldText
{
    public $component = 'field-text';
    public $arkPrefix = '';

    public function __construct($id, $props = [])
    {
        parent::__construct($id, $props);
        if (isset($props['arkPrefix'])) {
            $this->arkPrefix = $props['arkPrefix'];
        }
    }

    public function getConfig()
    {
        $config = parent::getConfig();
        $config['arkPrefix'] = $this->arkPrefix;
        return $config;
    }
}