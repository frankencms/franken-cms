<?php

namespace FrankenCms\Enums;

enum PostType: string
{
    case POST = 'post';
    case PAGE = 'page';
    case CUSTOM = 'custom';
}
