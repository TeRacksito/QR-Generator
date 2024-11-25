<?php

require '../vendor/autoload.php';

use Teracksito\QrGenerator\QRGenerator;
use Teracksito\QrGenerator\Utils\Constants;

class TestDataGenerator
{
    const DEFAULT_PATH = '/tests/data/';
    const DEFAULT_TOKEN = '0123456789';
    const DEFAULT_SEPARATOR = '-';

    private static $path;
    private static $token;
    private static $separator;

    public static function main($argv)
    {
        self::$path = Constants::PROJECT_ROOT . self::DEFAULT_PATH;
        self::$token = self::DEFAULT_TOKEN;
        self::$separator = self::DEFAULT_SEPARATOR;

        foreach ($argv as $arg) {
            if ($arg === '--help') {
                self::printHelp();
                return;
            } elseif (strpos($arg, '--token=') === 0) {
                self::$token = substr($arg, 8);
            } elseif (strpos($arg, '--separator=') === 0) {
                self::$separator = substr($arg, 12);
            } elseif ($arg !== $argv[0]) {
                self::$path = $arg;
            }
        }

        if (!is_dir(self::$path)) {
            mkdir(self::$path, 0775, true);
        }

        $json = file_get_contents(Constants::PROJECT_ROOT . '/src/data/qr_levels.json');
        $data = json_decode($json, true);
        $versions = $data;

        $total = array_reduce($versions, function ($acc, $eccLevels) {
            return $acc + count($eccLevels);
        }, 0);

        $count = 0;

        foreach ($versions as $version => $eccLevels) {
            printf("Working on version %d\n", $version);
            foreach ($eccLevels as $eccLevel => $schemas) {
                $count++;
                printf("\t- Progress: %.2f%% - ", ($count / $total) * 100);
                echo "Generating test data for version $version, ECC $eccLevel";

                $pad_length = $version > 9 ? 3 : 2;

                $data = self::genData(array_reduce($schemas, function ($acc, $schema) {
                    return $acc + $schema['data'] * $schema['blocks'];
                }, 0) - $pad_length);

                $qr = new QRGenerator();
                $info = $qr->generate($data, $eccLevel, $version);

                echo " -> version $version, ECC $eccLevel, " . PHP_EOL;

                $filename = sprintf('ver_%d-ecc_%s.json', $version, $eccLevel);
                printf("\t\t- Saving test data for version %d to %s\n", $version, self::$path . $filename);
                self::saveTestData(self::$path . $filename, [
                    'version' => $info['version'],
                    'ecc' => $info['ecc_level'],
                    'mask' => $info['mask'],
                    'data' => $data,
                    'map' => $qr->masked_map
                ]);

                unset($data, $qr, $info);
            }
            gc_collect_cycles();
        }
    }

    private static function genData(int $target_length): string
    {
        $token_length = strlen(self::$token . self::$separator);
        $data = str_repeat(self::$token . self::$separator, ceil($target_length / $token_length));
        return substr($data, 0, $target_length);
    }

    private static function saveTestData($path, $data)
    {
        file_put_contents($path, json_encode($data));
    }

    private static function printHelp()
    {
        echo "TestDataGenerator - A tool to generate test data for the QR Generator\n";
        echo "Assuming the current QR Generator is functional, this tool generates test for each version and error correction level.\n\n";
        echo "Usage: php TestDataGenerator.php [path] [--token=TOKEN] [--separator=SEPARATOR]\n";
        echo "Options:\n";
        echo "  path            The path where to save the data (default: " . self::DEFAULT_PATH . ")\n";
        echo "  --token=TOKEN   The token to include in the test data (default: " . self::DEFAULT_TOKEN . ")\n";
        echo "  --separator=SEP The separator to use in the test data (default: " . self::DEFAULT_SEPARATOR . ")\n";
    }
}

if (php_sapi_name() == 'cli') {
    TestDataGenerator::main($argv);
}
