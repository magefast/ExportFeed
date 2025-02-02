<?php

namespace Magefast\ExportFeed\Api;

use Magefast\ExportFeed\Service\StopRule;

interface ExportFeedInterface
{
    public const MAX_LENGTH_NAME = 150;

    public const UKR_SYMBOLS_NAME = ['ї', 'Ї', 'і', 'І'];

    public const EXPORT_TYPE_PROM_YML = [
        'label' => 'Prom YML',
        'value' => 'YmlProm',
        'file_type' => 'xml'
    ];

    public const EXPORT_TYPE_GOOGLE_SHOPPING_XML = [
        'label' => 'Google Shopping XML',
        'value' => 'XmlGoogleShopping',
        'file_type' => 'xml'
    ];

    public const EXPORT_TYPE_GOOGLE_SHOPPING_TSV = [
        'label' => 'Google Shopping TSV',
        'value' => 'TsvGoogleShopping',
        'file_type' => 'tsv'
    ];

    public const EXPORT_TYPE_ROZETKA = [
        'label' => 'Rozetka XML',
        'value' => 'Rozetka',
        'file_type' => 'xml'
    ];

    public const EXPORT_TYPE = [
        self::EXPORT_TYPE_PROM_YML,
        self::EXPORT_TYPE_GOOGLE_SHOPPING_XML,
        self::EXPORT_TYPE_GOOGLE_SHOPPING_TSV,
        self::EXPORT_TYPE_ROZETKA
    ];

    public const EXPORT_STOP_RULES = [
        [
            'label' => 'Stop out of stock products & qty low then 1',
            'value' => 'outOfStockQtyLow1',
            'name' => 'Out of Stock & Qty 0'
        ],
        [
            'label' => 'Stop products with Width/Height low than 500px',
            'value' => 'imgSizeLow500px',
            'name' => 'IMG Size Low 500'
        ],
        [
            'label' => 'Stop products without any image',
            'value' => 'anyImg',
            'name' => 'No Images'
        ],
        [
            'label' => 'Stop products with Length of Name more then ' . self::MAX_LENGTH_NAME,
            'value' => 'maxNameLength',
            'name' => 'Max Name length ' . self::MAX_LENGTH_NAME
        ],
        [
            'label' => 'Stop products with Mixed RU/UKR symbols in Name',
            'value' => 'mixedRuUkrContentName',
            'name' => 'UKR symbols in RU Name'
        ],
        [
            'label' => 'Stop products with Minimal Profit - ' . StopRule::MIN_PROFIT,
            'value' => 'minProfit',
            'name' => 'Min Profit ' . StopRule::MIN_PROFIT
        ],
        [
            'label' => 'Stop products without Rozetka Price',
            'value' => 'rozetkaPrice',
            'name' => 'Rozetka Price'
        ],
    ];
}
