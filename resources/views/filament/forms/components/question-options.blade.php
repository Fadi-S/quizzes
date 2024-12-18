<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    {{ $field->getName() }}
</x-dynamic-component>
