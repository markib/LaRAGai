<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
uses(RefreshDatabase::class)->in('Feature', 'Unit');

require __DIR__ . '/Helpers/OllamaMock.php';
require __DIR__ . '/Helpers/VectorAssertions.php';
