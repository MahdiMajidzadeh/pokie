<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. Of course, you may need to change it using the "pest()" function to bind a
| different classes or traits.
|
*/

uses(Tests\TestCase::class)->in('Feature', 'Unit');
uses(Illuminate\Foundation\Testing\RefreshDatabase::class)->in('Feature', 'Unit');
