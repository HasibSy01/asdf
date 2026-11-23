<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRMarkupSVG;

class QrSvgService
{
    /**
     * Hasilkan Data URI SVG QR Code (offline, tanpa ketergantungan php-gd atau CDN).
     */
    public static function renderDataUri(string $data): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => true,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'svgAddXmlHeader' => false,
        ]);

        return (new QRCode($options))->render($data);
    }
}

