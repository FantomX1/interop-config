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
class ConfigDumperCommand extends AbstractCommand
{
    const COMMAND_CLI_NAME = 'generate-config';

    const HELP_TEMPLATE = <<< EOH

<info>Usage:</info>

  %s [-h|--help|help] <configFile> <className>

<info>Arguments:</info>

  <info>-h|--help|help</info>    This usage message
  <info><configFile></info>      Path to a config file or php://stdout for which to generate configuration.
                    If the file does not exist, it will be created. If it does exist, it must return an 
                    array, and the file will be updated with new configuration.
  <info><className></info>       Name of the class to reflect and for which to generate dependency configuration.

Reads the provided configuration file (creating it if it does not exist), and injects it with config dependency 
configuration for the provided class name, writing the changes back to the file.
EOH;

    /**
     * @var ConfigDumper
     */
    private $configDumper;

    public function __construct(ConsoleHelper $helper = null, ConfigDumper $configReader = null)
    {
        parent::__construct($helper);
        $this->configDumper = $configReader ?: new ConfigDumper($this->helper);
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
            $config = $this->configDumper->createConfig($arguments->config, $arguments->class);
        } catch (InvalidArgumentException $e) {
            $this->helper->writeErrorMessage(
                sprintf('Unable to create config for "%s": %s', $arguments->class, $e->getMessage())
            );
            $this->help();
            return 1;
        }

        file_put_contents($arguments->configFile, $this->configDumper->dumpConfigFile($config) . PHP_EOL);

        $this->helper->writeLine(sprintf('<info>[DONE]</info> Changes written to %s', $arguments->configFile));
        return 0;
    }

    protected function checkFile(string $configFile): ?\stdClass
    {
        if (!is_writable(dirname($configFile)) && 'php://stdout' !== $configFile) {
            return $this->createErrorArgument(sprintf(
                'Cannot create configuration at path "%s"; not writable.',
                $configFile
            ));
        }
        return null;
    }
}
