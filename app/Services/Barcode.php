<?php
namespace App\Services;

/**
 * Pure PHP Barcode Generator (Code128B).
 * Zero external dependencies. Generates crisp, scalable vector SVGs and data URIs.
 * Perfect for patient MRN wristbands, specimen labels, ticket slips, and receipts.
 */
class Barcode
{
    private const PATTERNS = [
        "212222","222122","222221","121223","121322","131222","122213","122312","132212","221213",
        "221312","231212","112232","122132","122231","113222","123122","123221","223211","221132",
        "221231","213212","223112","312131","311222","321122","321221","312212","322112","322211",
        "212123","212321","232121","111323","131123","131321","112313","132113","132311","211313",
        "231113","231311","112133","112331","132131","113123","113321","133121","313121","211331",
        "231131","213113","213311","213131","311123","311321","331121","312113","312311","332111",
        "314111","221411","431111","111224","111422","121124","121421","141122","141221","112214",
        "112412","122114","122411","142112","142211","241211","221114","413111","241112","134111",
        "111242","121142","121241","114212","124112","124211","411212","421112","421211","212141",
        "214121","412121","111143","111341","131141","114113","114311","411113","411311","113141",
        "114131","311141","411131","211412","211214","211232","2331112"
    ];

    /**
     * Generate Code128 SVG string.
     * @param string $code Data to encode (alphanumeric)
     * @param int $height Height of barcode in px
     * @param int $moduleWidth Width of thin bar in px
     * @param bool $showText Whether to render human readable text underneath
     */
    public static function svg(string $code, int $height = 50, int $moduleWidth = 2, bool $showText = true): string
    {
        $code = trim($code);
        if ($code === '') return '';

        // Start Code B is index 104
        $chars = [104];
        $checksum = 104;

        $len = strlen($code);
        for ($i = 0; $i < $len; $i++) {
            $ascii = ord($code[$i]);
            $val = $ascii - 32;
            if ($val < 0 || $val > 95) $val = 0;
            $chars[] = $val;
            $checksum += $val * ($i + 1);
        }

        $checksum %= 103;
        $chars[] = $checksum;
        $chars[] = 106; // Stop character

        // Convert to bar modules
        $modules = '';
        foreach ($chars as $c) {
            $pattern = self::PATTERNS[$c] ?? self::PATTERNS[0];
            $isBar = true;
            for ($j = 0; $j < strlen($pattern); $j++) {
                $count = (int) $pattern[$j];
                $modules .= str_repeat($isBar ? '1' : '0', $count);
                $isBar = !$isBar;
            }
        }

        $totalModules = strlen($modules);
        $totalWidth = $totalModules * $moduleWidth;
        $totalHeight = $showText ? ($height + 18) : $height;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $totalWidth . ' ' . $totalHeight . '" width="' . $totalWidth . '" height="' . $totalHeight . '" style="display:inline-block; max-width:100%; height:auto;">';
        $svg .= '<rect width="100%" height="100%" fill="transparent"/>';

        $x = 0;
        $barStart = null;
        for ($i = 0; $i < $totalModules; $i++) {
            if ($modules[$i] === '1') {
                if ($barStart === null) $barStart = $i * $moduleWidth;
            } else {
                if ($barStart !== null) {
                    $w = ($i * $moduleWidth) - $barStart;
                    $svg .= '<rect x="' . $barStart . '" y="0" width="' . $w . '" height="' . $height . '" fill="#000000"/>';
                    $barStart = null;
                }
            }
        }
        if ($barStart !== null) {
            $w = ($totalModules * $moduleWidth) - $barStart;
            $svg .= '<rect x="' . $barStart . '" y="0" width="' . $w . '" height="' . $height . '" fill="#000000"/>';
        }

        if ($showText) {
            $svg .= '<text x="' . ($totalWidth / 2) . '" y="' . ($height + 14) . '" font-family="monospace, ui-monospace, Courier" font-size="12" font-weight="700" text-anchor="middle" fill="#000000">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</text>';
        }

        $svg .= '</svg>';
        return $svg;
    }

    /**
     * Return base64 data URI for embedding directly into img tags: <img src="data:image/svg+xml;base64,...">
     */
    public static function dataUri(string $code, int $height = 50, int $moduleWidth = 2, bool $showText = true): string
    {
        $svg = self::svg($code, $height, $moduleWidth, $showText);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
