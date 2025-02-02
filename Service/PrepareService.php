<?php

namespace Magefast\ExportFeed\Service;

class PrepareService
{
    public const EAN_LENGTH = 10;

    public function mbUcfirst($string, $encoding = 'UTF-8')
    {
        $firstChar = mb_substr($string, 0, 1, $encoding);
        $then = mb_substr($string, 1, null, $encoding);
        return mb_strtoupper($firstChar, $encoding) . $then;
    }

    public function prepareDescription($text, $stripTags = true)
    {
        if(empty($text)) {
            return '';
        }

        if (!empty($text) && $stripTags) {
            $text = strip_tags($text);
        }

        $seoText = [];
        $seoText[] = '0987654321';
        $seoText[] = "qwerty2";
        $seoText[] = 'Интернет-магазин "XXX"';
        $seoText[] = 'Интернет-магазин «XXX»';
        $seoText[] = 'qwerty';
        $seoText[] = '';
        $seoText[] = '<0x0b>';
        $seoText[] = '0x0b';

        if (!empty($text)) {
            $text = str_replace($seoText, "", $text);
        }
        return $text;
    }

    public function prepareText($value): string
    {
        if (empty($value)) {
            return '';
        }

        $value = strip_tags(
            str_replace(
                '&', " ", str_replace(
                    '&nbsp;', " ", str_replace(
                        '&mdash;', " ", str_replace(
                            '&laquo;', "", str_replace(
                                '&ndash;', "", str_replace(
                                    '&amp;', "",
                                    str_replace('&deg;', "", str_replace('&raquo;', "", $value))
                                )
                            )
                        )
                    )
                )
            )
        );

        if (!empty($value)) {
            $value = trim($value);
        }

        return $value;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isEanCorrect($value = null): bool
    {
        $valueStrlen = 0;
        if (!empty($value)) {
            $value = trim($value);
        }
        if (!empty($value)) {
            $valueStrlen = strlen($value);
        }

        if ($valueStrlen > self::EAN_LENGTH) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getDateTimeNow(): string
    {
        return date("Y-m-d H:i");
    }
}
