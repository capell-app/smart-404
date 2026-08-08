<?php

declare(strict_types=1);

use Capell\Smart404\Tests\Smart404TestCase;

pest()->extend(Smart404TestCase::class)->group('smart-404')->in('.');
