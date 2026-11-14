<?php

declare(strict_types=1);

use PHPUnit\Framework\SkippedTest;

/*
 * composer.json keeps the tests co-located in app/ out of the class map. That
 * exclusion must stay scoped to app/: a project-wide "*Test.php" pattern also
 * drops vendor classes such as PHPUnit's SkippedTest, and every skipped test crashes.
 */
it('keeps vendor classes whose names end in Test autoloadable', function (): void {
    expect(interface_exists(SkippedTest::class))->toBeTrue();
});
