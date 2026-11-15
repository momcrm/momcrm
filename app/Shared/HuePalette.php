<?php

declare(strict_types=1);

namespace App\Shared;

/**
 * Named hues (0–359 on the OKLCH colour wheel) that projects, groups and
 * choices are tagged with. Records store any hue, or none for neutral grey;
 * these presets are just the swatches defaults and sample data use. The
 * frontend draws every hue at a fixed lightness and chroma per theme, so all
 * of them stay readable.
 */
final class HuePalette
{
    public const int ROSE = 0;

    public const int RED = 30;

    public const int ORANGE = 60;

    public const int AMBER = 90;

    public const int LIME = 120;

    public const int GREEN = 150;

    public const int TEAL = 180;

    public const int CYAN = 210;

    public const int BLUE = 240;

    public const int INDIGO = 270;

    public const int VIOLET = 300;

    public const int PINK = 330;

    /**
     * A hue picked from a name, so the same name (in the same kind of record,
     * such as "label:Bug") always gets the same colour.
     */
    public static function forName(string $kind, string $name): int
    {
        return crc32($kind.':'.mb_strtolower(trim($name))) % 360;
    }
}
