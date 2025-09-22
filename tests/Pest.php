<?php

use FrankenCms\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

// Use modern Pest v4 configuration API
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in(__DIR__);
