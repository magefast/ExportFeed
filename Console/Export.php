<?php
/**
 * @author magefast@gmail.com www.magefast.com
 * @command php bin/magento feed:export
 */

namespace Magefast\ExportFeed\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Export extends Command
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('exportfeed');
        $this->setDescription('Export Feed command line');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $generatorModel = $objectManager->create('\Magefast\ExportFeed\Service\Generator');

        $output->writeln("Export Feed... start");

        $generatorModel->runExportAll();

        $output->writeln("Export Feed... finish");

        unset($feed);
        unset($generator);
    }
}
