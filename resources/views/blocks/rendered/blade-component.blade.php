<x-dynamic-component
    :component="sprintf('blocks.%s', $name)"
    :attributes="attributes_to_attribute_bag($attributes)"
/>
