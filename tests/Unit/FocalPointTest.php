<?php

use FrankenCms\Support\FocalPoint;

describe('normalize', function () {
    test('parses the percent string format the pickers store', function () {
        expect(FocalPoint::normalize('94% 22%'))->toBe(['x' => 94, 'y' => 22]);
    });

    test('accepts the legacy array format', function () {
        expect(FocalPoint::normalize(['x' => 30, 'y' => 70]))->toBe(['x' => 30, 'y' => 70]);
    });

    test('defaults to center for null, garbage, or partial input', function () {
        expect(FocalPoint::normalize(null))->toBe(['x' => 50, 'y' => 50])
            ->and(FocalPoint::normalize('not a focal point'))->toBe(['x' => 50, 'y' => 50])
            ->and(FocalPoint::normalize(['x' => 30]))->toBe(['x' => 30, 'y' => 50])
            ->and(FocalPoint::normalize(123))->toBe(['x' => 50, 'y' => 50]);
    });

    test('clamps out-of-range values to 0-100', function () {
        expect(FocalPoint::normalize('150% -20%'))->toBe(['x' => 100, 'y' => 0]);
    });

    test('handles decimal percentages', function () {
        expect(FocalPoint::normalize('33.4% 66.7%'))->toBe(['x' => 33, 'y' => 67]);
    });
});

describe('formatting', function () {
    test('toPercentString produces the picker format', function () {
        expect(FocalPoint::toPercentString(['x' => 94, 'y' => 22]))->toBe('94% 22%');
    });

    test('toCss produces an object-position declaration', function () {
        expect(FocalPoint::toCss('94% 22%'))->toBe('object-position: 94% 22%;');
    });
});
