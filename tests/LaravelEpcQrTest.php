<?php

namespace Ccharz\LaravelEpcQr\Tests;

use Ccharz\LaravelEpcQr\EPCQR;
use Ccharz\LaravelEpcQr\LaravelEpcQr;
use Endroid\QrCode\Writer\Result\PngResult;
use Endroid\QrCode\Writer\Result\SvgResult;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class LaravelEpcQrTest extends TestCase
{
    public function test_basic_generation(): void
    {
        $output = EPCQR::amount(150)
            ->receiver('AT000000000000000000', 'ABCDATWW', 'Christian C')
            ->note('test')
            ->purpose('AT12')
            ->text('Test Überweisungstext')
            ->output();

        $this->assertSame(
            "BCD\n002\n8\nSCT\nABCDATWW\nChristian C\nAT000000000000000000\nEUR150.00\nAT12\n\nTest Überweisungstext\ntest",
            $output
        );
    }

    public function test_reference(): void
    {
        $output = EPCQR::amount(150)
            ->receiver('AT000000000000000000', 'ABCDATWW', 'Christian C')
            ->note('test')
            ->purpose('AT12')
            ->reference('RF18000000000539007547034')
            ->output();

        $this->assertSame(
            "BCD\n002\n8\nSCT\nABCDATWW\nChristian C\nAT000000000000000000\nEUR150.00\nAT12\nRF18000000000539007547034\n\ntest",
            $output
        );
    }

    public function test_encoding(): void
    {
        $output = EPCQR::amount(150)
            ->encoding(LaravelEpcQr::ENCODING_UTF_8)
            ->receiver('AT000000000000000000', 'ABCDATWW', 'Christian C')
            ->note('test')
            ->purpose('AT12')
            ->text('Test Überweisungstext')
            ->output();

        $this->assertSame(
            "BCD\n002\n1\nSCT\nABCDATWW\nChristian C\nAT000000000000000000\nEUR150.00\nAT12\n\nTest Überweisungstext\ntest",
            $output
        );
    }

    public function test_invalid_encoding(): void
    {
        $this->expectException('InvalidArgumentException');

        $output = EPCQR::amount(150)
            ->encoding(5000)
            ->output();
    }

    public function test_build(): void
    {
        $output = EPCQR::amount(150)
            ->size(500)
            ->margin(20)
            ->build();

        $this->assertTrue($output instanceof PngResult);

        $image = $output->getImage();

        $this->assertSame(540, imagesx($image));
        $this->assertSame(540, imagesy($image));
    }

    public function test_build_svg(): void
    {
        $output = EPCQR::amount(150)
            ->imageFormat('svg')
            ->build();

        $this->assertTrue($output instanceof SvgResult);
    }

    public function test_stream(): void
    {
        $output = EPCQR::amount(150)
            ->imageFormat('svg')
            ->stream();

        $this->assertInstanceOf(Response::class, $output);

        $this->assertSame(200, $output->getStatusCode());

        $this->assertNotEmpty($output->getContent());
    }

    public function test_store(): void
    {
        Storage::fake('test_disk');

        $output = EPCQR::amount(150)
            ->imageFormat('svg')
            ->save('test.svg', 'test_disk');

        Storage::disk('test_disk')->assertExists('test.svg');
    }
}
