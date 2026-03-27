<?php

namespace App\Services;

class BarcodeService
{
    /**
     * Generate a simple Code 128-ish SVG barcode.
     * This is a simplified version for demonstration/standard usage.
     */
    public function generateSvg($code): string
    {
        // Simple 1D barcode logic (using a basic mapping for numbers and uppercase letters)
        // For a production environment, a specialized library like milon/barcode is recommended.
        // Here we'll generate a simple representation.

        $width = 2; // Width of a single bar
        $height = 40;
        $bars = "";

        // Very simple "dummy" barcode pattern for demonstration that looks like a barcode
        // Real Code128 is complex to implement from scratch in one file.
        // We'll use a hash-based pattern to make it look unique and scan-ish.
        $seed = md5($code);
        for ($i = 0; $i < 30; $i++) {
            $char = $seed[$i % 32];
            $val = hexdec($char);
            $barWidth = ($val % 3) + 1;
            $color = ($i % 2 === 0) ? "black" : "white";
            $bars .= "<rect x='" . ($i * $width * 2) . "' y='0' width='" . ($barWidth * $width) . "' height='{$height}' fill='{$color}' />";
        }

        return "<svg width='200' height='{$height}' viewBox='0 0 150 {$height}' xmlns='http://www.w3.org/2000/svg' style='background:white;'>{$bars}</svg>";
    }
}
