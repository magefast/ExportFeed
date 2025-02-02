<?php

namespace Magefast\ExportFeed\Service;

class CategoryBrandRule
{
    /**
     * @param array $data
     * @param array|null $categoriesRule
     * @param array|null $exportRule
     * @return void
     */
    public function execute(array &$data, ?array $categoriesRule, ?array $exportRule)
    {
        if ($exportRule == null) {
            return;
        }

        if ($categoriesRule == null || count($categoriesRule) == 0) {
            return;
        }

        foreach ($data as $key => $value) {
            $needExport = true;

            if (is_null($value['product_category_ids']) || count($value['product_category_ids']) == 0) {
                $needExport = false;
            }

            $categoriesInExportSettings = false;
            foreach ($value['product_category_ids'] as $valueC) {
                if (isset($categoriesRule[intval($valueC)])) {
                    $categoriesInExportSettings = true;
                    if (isset($exportRule[intval($valueC)])) {
                        $manufacturerInExportSettings = false;
                        foreach ($exportRule as $keyR => $valueR) {
                            if ($value['product_manufacturer_id'] && isset($valueR[$value['product_manufacturer_id']])) {
                                $manufacturerInExportSettings = true;
                            }
                        }
                        if (!$manufacturerInExportSettings) {
                            $categoriesInExportSettings = false;
                        }
                    }
                }
            }

            if (!$categoriesInExportSettings) {
                $needExport = false;
            }

            if (!$needExport) {
                unset($data[$key]);
            }
        }
    }
}
