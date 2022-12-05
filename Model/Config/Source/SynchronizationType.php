<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Crowdin\Model\Config\Source;

class SynchronizationType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 0, 'label' => __(' All (default)')],
            ['value' => 1, 'label' => __('Specific categories and products')],
            ['value' => 2, 'label' => __('All except specific categories and products')]
        ];
    }
}
