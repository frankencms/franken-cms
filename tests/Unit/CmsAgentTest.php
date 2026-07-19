<?php

use FrankenCms\Ai\CmsAgent;
use Laravel\Ai\Contracts\Agent;

test('implements the SDK Agent contract', function () {
    expect(new CmsAgent)->toBeInstanceOf(Agent::class);
});

test('exposes constructor config through Promptable fallback methods', function () {
    $agent = new CmsAgent(
        instructions: 'Write plainly.',
        maxTokens: 750,
        temperature: 0.4,
    );

    expect($agent->instructions())->toBe('Write plainly.')
        ->and($agent->maxTokens())->toBe(750)
        ->and($agent->temperature())->toBe(0.4);
});
