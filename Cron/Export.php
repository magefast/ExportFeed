<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Magefast\ExportFeed\Cron;

use Psr\Log\LoggerInterface;
use Magefast\ExportFeed\Service\Generator;

class Export
{
    protected $logger;
    protected $generator;

    public function __construct(
        LoggerInterface $logger,
        Generator       $generator
    )
    {
        $this->logger = $logger;
        $this->generator = $generator;
    }

    public function execute()
    {
        $this->logger->info('Cron Export Feed - START');

        $this->generator->runExportAll();

        $this->logger->info('Cron Export Feed - FINISH');

        return $this;
    }
}
