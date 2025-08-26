<?php

namespace App\Helpers;

class ColorHelper
{
    /**
     * Convert HEX color to OKLCH format
     * 
     * @param string $hex Hex color code (e.g., #f59e0b)
     * @param bool $darkMode Whether to generate a dark mode variation
     * @return string OKLCH color string
     */
    public static function hexToOklch(string $hex, bool $darkMode = false): string
    {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Convert hex to RGB
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        
        // Known color mappings for common Filament colors
        $colorMappings = [
            // Amber variants
            '#f59e0b' => ['light' => 'oklch(0.647 0.164 56.2)', 'dark' => 'oklch(0.7 0.164 56.2)'],
            '#f97316' => ['light' => 'oklch(0.647 0.164 56.2)', 'dark' => 'oklch(0.7 0.164 56.2)'],
            
            // Red variants
            '#ef4444' => ['light' => 'oklch(0.577 0.245 27.325)', 'dark' => 'oklch(0.65 0.245 27.325)'],
            '#dc2626' => ['light' => 'oklch(0.577 0.245 27.325)', 'dark' => 'oklch(0.65 0.245 27.325)'],
            
            // Green variants
            '#10b981' => ['light' => 'oklch(0.648 0.15 160)', 'dark' => 'oklch(0.7 0.15 160)'],
            '#059669' => ['light' => 'oklch(0.648 0.15 160)', 'dark' => 'oklch(0.7 0.15 160)'],
            
            // Blue variants
            '#3b82f6' => ['light' => 'oklch(0.6 0.151 251.8)', 'dark' => 'oklch(0.65 0.151 251.8)'],
            '#2563eb' => ['light' => 'oklch(0.6 0.151 251.8)', 'dark' => 'oklch(0.65 0.151 251.8)'],
            
            // Gray variants
            '#71717a' => ['light' => 'oklch(0.556 0 0)', 'dark' => 'oklch(0.708 0 0)'],
            '#6b7280' => ['light' => 'oklch(0.556 0 0)', 'dark' => 'oklch(0.708 0 0)'],
        ];
        
        // Check if we have a direct mapping
        $originalHex = '#' . $hex;
        if (isset($colorMappings[$originalHex])) {
            return $colorMappings[$originalHex][$darkMode ? 'dark' : 'light'];
        }
        
        // Convert RGB to approximate OKLCH values
        $lightness = static::rgbToLightness($r, $g, $b);
        $chroma = static::rgbToChroma($r, $g, $b);
        $hue = static::rgbToHue($r, $g, $b);
        
        // Adjust for dark mode
        if ($darkMode) {
            // Increase lightness for better visibility in dark mode
            $lightness = min(1, $lightness + 0.1);
            // Slightly reduce chroma for better dark mode appearance
            $chroma = max(0, $chroma - 0.02);
        }
        
        return sprintf('oklch(%.3f %.3f %.1f)', $lightness, $chroma, $hue);
    }
    
    /**
     * Convert RGB to approximate lightness value for OKLCH
     */
    private static function rgbToLightness(float $r, float $g, float $b): float
    {
        // Simple luminance calculation
        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        
        // Convert luminance to OKLCH lightness (approximate)
        return pow($luminance, 1/2.4);
    }
    
    /**
     * Convert RGB to approximate chroma value for OKLCH
     */
    private static function rgbToChroma(float $r, float $g, float $b): float
    {
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        
        // Calculate saturation-like value
        $delta = $max - $min;
        
        if ($max == 0) {
            return 0;
        }
        
        // Convert to approximate OKLCH chroma
        $saturation = $delta / $max;
        return $saturation * 0.3; // Scale down for OKLCH range
    }
    
    /**
     * Convert RGB to hue value for OKLCH
     */
    private static function rgbToHue(float $r, float $g, float $b): float
    {
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;
        
        if ($delta == 0) {
            return 0;
        }
        
        $hue = 0;
        if ($max == $r) {
            $hue = 60 * ((($g - $b) / $delta) + ($g < $b ? 6 : 0));
        } elseif ($max == $g) {
            $hue = 60 * (($b - $r) / $delta + 2);
        } else {
            $hue = 60 * (($r - $g) / $delta + 4);
        }
        
        return $hue;
    }
}