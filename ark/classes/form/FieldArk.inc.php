<?php
/**
 * @file plugins/pubIds/ark/classes/form/FieldArk.inc.php
 *
 * Copyright (c) 2026 Lury Morais
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FieldUrn
 * @ingroup classes_controllers_form
 *
 * @brief A field for entering a ARK.
 */

namespace Plugins\PubIds\Ark\Classes\Form;

use PKP\components\forms\FieldText;

class FieldArk extends FieldText
{
    public $component = 'field-ark';
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

