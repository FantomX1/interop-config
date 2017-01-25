<?php
/**
 * Sandro Keil (https://sandro-keil.de)
 *
 * @link      http://github.com/sandrokeil/interop-config for the canonical source repository
 * @copyright Copyright (c) 2017-2017 Sandro Keil
 * @license   http://github.com/sandrokeil/interop-config/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Interop\Config\Tool;

use Interop\Config\Exception\InvalidArgumentException;

/**
 * Command to dump a configuration from a factory class
 *
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */
class ConfigReaderCommand extends AbstractCommand
{
    const COMMAND_CLI_NAME = 'display-config';

    const HELP_TEMPLATE = <<< EOH

<info>Usage:</info>

  %s [-h|--help|help] <configFile> <className>

<info>Arguments:</info>

  <info>-h|--help|help</info>    This usage message
  <info><configFile></info>      Path to a config file for which to displa configuration.
                    It must return an array / ArrayObject.
  <info><className></info>       Name of the class to reflect and for which to display dependency configuration.

Reads the provided configuration file and displays dependency configuration for the provided class name.
EOH;

    /**
     * @var ConfigReader
     */
    private $configReader;

    public function __construct(ConsoleHelper $helper = null, ConfigReader $configReader = null)
    {
        parent::__construct($helper);
        $this->configReader = $configReader ?: new ConfigReader($this->helper);
    }

    /**
     * @param array $args Argument list, minus script name
     * @return int Exit status
     */
    public function __invoke(array $args): int
    {
        $arguments = $this->parseArgs($args);

        switch ($arguments->command) {
            case self::COMMAND_HELP:
                $this->help();
                return 0;
            case self::COMMAND_ERROR:
                $this->helper->writeErrorLine($arguments->message);
                $this->help();
                return 1;
            case self::COMMAND_DUMP:
                // fall-through
            default:
                break;
        }

        try {
            $config = $this->configReader->readConfig($arguments->config, $arguments->class);
        } catch (InvalidArgumentException $e) {
            $this->helper->writeErrorMessage(
                sprintf('Unable to read config for "%s": %s', $arguments->class, $e->getMessage())
            );
            $this->help();
            return 1;
        }

        $this->helper->write($this->configReader->dumpConfigFile($config) . PHP_EOL);

        return 0;
    }

    protected function checkFile(string $configFile): ?\stdClass
    {
        if (!is_readable(dirname($configFile))) {
            return $this->createErrorArgument(sprintf(
                'Cannot read configuration at path "%s".',
                $configFile
            ));
        }
        return null;
    }
}
