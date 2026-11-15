<?php

declare(strict_types=1);

use App\Shared\HuePalette;

it('picks the same hue for the same name in the same kind of record', function (): void {
    $hue = HuePalette::forName('label', 'Bug');

    expect($hue)->toBeGreaterThanOrEqual(0)->toBeLessThan(360)
        ->and(HuePalette::forName('label', ' bug '))->toBe($hue)
        ->and(HuePalette::forName('label', 'Feature'))->not->toBe($hue)
        ->and(HuePalette::forName('project', 'Bug'))->not->toBe($hue);
});
