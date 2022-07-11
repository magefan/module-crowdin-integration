<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types = 1);

namespace Magefan\Crowdin\Model;

use Magento\Framework\Math\Random;

/**
 * Class Mapped manage mapped values.
 */
class Mapped
{
    /**
     * @var Random 
     */
    protected $mathRandom;

    /**
     * @param Random $mathRandom
     */
    public function __construct(
        Random $mathRandom
    ) {
        $this->mathRandom = $mathRandom;
    }

    /**
     * Generate a storable representation of a value
     *
     * @param int|float|string|array $value
     * @return string
     */
    public function serializeValue($value)
    {
        if (is_numeric($value)) {
            $data = (float) $value;
            return (string) $data;
        } elseif (is_array($value)) {
            $data = [];
            foreach ($value as $attributeCode => $isSyncEnabled) {
                if (!array_key_exists($attributeCode, $data)) {
                    $data[$attributeCode] = $isSyncEnabled;
                }
            }
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            return '';
        }
    }

    /**
     * Create a value from a storable representation
     *
     * @param int|float|string $value
     * @return array
     */
    public function unserializeValue($value)
    {
        if (is_string($value) && !empty($value)) {
            return json_decode($value, true);
        } else {
            return [];
        }
    }

    /**
     * Check whether value is in form retrieved by _encodeArrayFieldValue()
     *
     * @param string|array $value
     * @return bool
     */
    protected function isEncodedArrayFieldValue($value)
    {
        if (!is_array($value)) {
            return false;
        }

        unset($value['__empty']);

        foreach ($value as $row) {
            if (!is_array($row)
                || !array_key_exists('is_crowdin_sync_enabled', $row)
                || !array_key_exists('magento_translatable_fields', $row)
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Encode value to be used in \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param array $value
     * @return array
     */
    protected function encodeArrayFieldValue(array $value)
    {
        $result = [];

        foreach ($value as $attributeCode => $isSyncEnabled) {
            $resultId = $this->mathRandom->getUniqueHash('_');
            $result[$resultId] = ['magento_translatable_fields' => $attributeCode, 'is_crowdin_sync_enabled' => $isSyncEnabled];
        }

        return $result;
    }

    /**
     * Decode value from used in \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param array $value
     * @return array
     */
    protected function decodeArrayFieldValue(array $value)
    {
        $result = [];

        unset($value['__empty']);

        foreach ($value as $row) {

            if (!is_array($row)
                || !array_key_exists('is_crowdin_sync_enabled', $row)
                || !array_key_exists('magento_translatable_fields', $row)
            ) {
                continue;
            }

            $result[$row['magento_translatable_fields']] = $row['is_crowdin_sync_enabled'];
        }

        return $result;
    }

    /**
     * Make value readable by \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param string|array $value
     * @return array
     */
    public function makeArrayFieldValue($value)
    {
        $value = $this->unserializeValue($value);

        if (!$this->isEncodedArrayFieldValue($value)) {
            $value = $this->encodeArrayFieldValue($value);
        }
        return $value;
    }

    /**
     * Make value ready for store
     *
     * @param string|array $value
     * @return string
     */
    public function makeStorableArrayFieldValue($value)
    {
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }

        ksort($value);

        return $this->serializeValue($value);
    }
}
