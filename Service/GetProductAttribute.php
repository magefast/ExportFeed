<?php

namespace Magefast\ExportFeed\Service;

class GetProductAttribute
{
    public function getAttributeContent($code, $product, $storeId, $stripTags = true): string
    {
        $value = '';
        $attributeValue = $product->getResource()->getAttribute($code)->getFrontend()->getValue($product, $storeId);
        if ($attributeValue && $attributeValue != '') {
            if ($stripTags) {
                $value = strip_tags($attributeValue);
            }
        }

        return $value;
    }
}
