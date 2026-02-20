<?php

use FrankenCms\Services\AiFeatureDetector;

describe('isPrismInstalled', function () {
    test('returns false when Prism service provider is not loaded', function () {
        expect(AiFeatureDetector::isPrismInstalled())->toBeFalse();
    });
});

describe('isAvailable', function () {
    test('returns false when Prism is not installed', function () {
        // Without Prism loaded, isAvailable should short-circuit to false
        expect(AiFeatureDetector::isAvailable())->toBeFalse();
    });
});
