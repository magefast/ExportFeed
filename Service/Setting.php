<?php

namespace Magefast\ExportFeed\Service;

use Magefast\ExportFeed\Api\ExportFeedInterface;

class Setting
{
    /**
     * @param $feed
     * @return string
     */
    public function getExportFileType($feed): string
    {
        $type = $feed->getData('type');

        foreach (ExportFeedInterface::EXPORT_TYPE as $t) {
            if ($t['value'] == $type) {
                return $t['file_type'];
            }
        }

        return 'txt';
    }
}
