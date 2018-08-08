<?php

namespace Productsup\Command;

use \Exception;
use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Logger\ConsoleLogger;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * Pup abstract command.
 */
abstract class AbstractCommand extends Command
{
    /**
     * Productsup logger.
     *
     * @var \Productsup\Logger
     */
    protected $logger;

    /**
     * Main configuration for all Pup binaries. Defines the minimal required
     * options: site and process.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->addOption('site', null, InputOption::VALUE_REQUIRED, 'Site ID')
            ->addOption('process', null, InputOption::VALUE_REQUIRED, 'Process ID', uniqid())
        ;
    }

    /**
     * Main initialize for all Pup Binaries. Sets up logger to multiple channels
     * and sets main options a properties.
     *
     * @param  InputInterface $input
     * @param  OutputInterface $output
     *
     * @return void
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $siteId = $input->getOption('site');
        $process = $input->getOption('process');

        putenv('PRODUCTSUP_PID='.$process);

        $logInfo = new \Productsup\LogInfo();
        $logInfo->name = '';
        $logInfo->site = $siteId;
        $logInfo->process = $process;

        $redisConfig = \System\Config::getSection('redis::notification');

        $redisConfig['channel'] = sprintf(
            '%s_%s.log',
            $siteId,
            substr(md5($siteId."a"), 0, 15).substr(md5($siteId."b"), 5, 5)
        );

        $this->logger = new \Productsup\Logger(
            array(
                'Shell' => new \Productsup\Handler\SymfonyConsoleHandler(\Psr\Log\LogLevel::DEBUG, 0, $output),
                'Redis' => new \Productsup\Handler\RedisHandler($redisConfig, 'notice', 0),
                'Gelf' => new \Productsup\Handler\GelfHandler(),
            ),
            $logInfo
        );
    }

    /**
     * Creates directory for specified file path and optionally touches file.
     *
     * @param  string $filePath File path to ensure
     * @param  boolean $touchFile Touch the file
     *
     * @return void
     *
     * @throws Exception If directory could not be created or file could not be
     *                   touched
     */
    protected function ensureFileIsWriteable($filePath, $touchFile = false)
    {
        $fileDir = dirname($filePath);

        if (!is_dir($fileDir)) {
            if (!mkdir($fileDir, 0777, true)) {
                throw new Exception(sprintf(
                    "Could not create directory '%s' for file '%s'.",
                    $fileDir,
                    basename($filePath)
                ));
            }
        }

        if ($touchFile) {
            if (!touch($filePath)) {
                throw new Exception(sprintf("Could not create file '%s'.", $filePath), 1);
            }
        }

        return;
    }
}
