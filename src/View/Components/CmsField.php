<?php

namespace FrankenCms\View\Components;

use FrankenCms\Services\CurrentPageService;
use Illuminate\View\Component;
use Illuminate\View\View;

class CmsField extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(protected CurrentPageService $pageService, public string $name, public string $type, public ?array $properties) {}

    public function render(): string | View
    {

        return view('franken-cms::components.cms-field', [
            'page' => $this->pageService->getPage(),
        ]);
    }
}
