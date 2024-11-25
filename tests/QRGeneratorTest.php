<?php

use Teracksito\QrGenerator\QRGenerator;
use Teracksito\QrGenerator\Utils\Constants;
use PHPUnit\Framework\TestCase;

class QRGeneratorTest extends TestCase
{
    /**
     * @dataProvider qrDataProvider
     */
    public function testQRGenerator($filename)
    {
        $data = json_decode(file_get_contents($filename), true);

        $qrGenerator = new QRGenerator();
        $qrGenerator->generate($data['data'], $data['ecc']);

        $this->assertEquals($data['map'], $qrGenerator->masked_map);
    }

    public static function qrDataProvider()
    {
        $files = glob(Constants::PROJECT_ROOT . '/tests/data/*.json');
        foreach ($files as $file) {
            yield basename($file) => [$file];
        }
    }
}
