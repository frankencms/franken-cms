<?php

namespace FrankenCms\Contracts;

interface PostContentInterface
{
    public function get(string $key): array | string | null;

    public function toArray(): array;
}
