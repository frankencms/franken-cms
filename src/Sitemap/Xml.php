<?php

declare(strict_types=1);

namespace FrankenCms\Sitemap;

class Xml
{
    public const DECLARATION = '<?xml version="1.0" encoding="UTF-8"?>';

    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
