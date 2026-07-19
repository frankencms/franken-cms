<?php

namespace FrankenCms\Ai;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class CmsAgent implements Agent
{
    use Promptable;

    public function __construct(
        protected string $instructions = 'You are a helpful assistant generating content for a CMS. Respond with only the requested content, no preamble.',
        protected ?int $maxTokens = null,
        protected ?float $temperature = null,
    ) {}

    public function instructions(): string
    {
        return $this->instructions;
    }

    public function maxTokens(): ?int
    {
        return $this->maxTokens;
    }

    public function temperature(): ?float
    {
        return $this->temperature;
    }
}
