<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Crowdin\Block\Adminhtml\Form;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Multiselect extends Field
{

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return parent::_getElementHtml($element) . "
        <link rel=\"stylesheet\" type=\"text/css\" href=\"" . $this->getViewFileUrl('Magefan_Crowdin::css/chosen.min.css') . "\">
        <style type='text/css'>
            body .chosen-container.chosen-container-multi {width: 100% !important;}
            #" . $element->getId() . " {display:none}
        </style>
        <script>
            require([
                'jquery',
                'Magefan_Crowdin/js/chosen.jquery.min',
            ], function ($, chosen) {
                $('#" . $element->getId() . "').chosen({
                    placeholder_text: '" . __('None selected') . "'
                });
            })
        </script>";
    }
}