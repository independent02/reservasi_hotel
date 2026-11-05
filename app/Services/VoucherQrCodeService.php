<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class VoucherQrCodeService
{
    public function dataUri(string $bookingCode): string
    {
        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString($bookingCode);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
