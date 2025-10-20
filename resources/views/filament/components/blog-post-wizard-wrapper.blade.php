<div
    x-data="{}"
    @insert-generated-content.window="
        const content = $event.detail[0].content || $event.detail.content;
        const componentId = $event.detail[0].componentId || $event.detail.componentId;

        // Find the specific Livewire component using its ID
        const formComponent = Livewire.find(componentId);

        if (formComponent) {
            // In Filament forms, data is stored in the 'data' property
            formComponent.set('data.post_content', content);
        }
    "
>
    @livewire('blog-post-wizard')
</div>
