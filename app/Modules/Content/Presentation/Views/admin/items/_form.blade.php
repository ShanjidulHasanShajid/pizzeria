{{-- Included by create and edit. $type: ContentType. $item: ContentItemView when editing, null when creating. --}}
@use('App\Modules\Content\Domain\Enums\FieldKind')
@use('App\Modules\Content\Presentation\Support\ContentLabels')
<x-admin.form-section :title="$type->singular()">
    @foreach ($type->fields() as $field)
        @php
            $text = ContentLabels::for($field->name);
            $value = $item?->value($field->name) ?? '';
        @endphp
        @switch($field->kind)
            @case(FieldKind::Image)
                <div class="sm:col-span-2">
                    <x-admin.image-upload :name="$field->name" :label="$text['label']" :current="$imageUrl($value)" :help="$text['help']" />
                </div>
                @break

            @case(FieldKind::Icon)
                <x-form.select :name="$field->name" :label="$text['label']" :options="ContentLabels::ICONS" :value="$value" placeholder="Choose an icon" required />
                @break

            @default
                <x-form.input :name="$field->name" :label="$text['label']" :value="$value" :help="$text['help']" :required="$field->isRequired" :maxlength="$field->maxLength" />
        @endswitch
    @endforeach
</x-admin.form-section>

<x-admin.form-section title="Visibility" :description="$type->isScheduled() ? 'Leave the dates empty to show it always. Times are Dhaka time.' : null">
    @if ($type->isScheduled())
        <x-form.input name="starts_at" type="datetime-local" label="Starts" :value="$item?->startsAt?->format('Y-m-d\TH:i')" />
        <x-form.input name="ends_at" type="datetime-local" label="Ends" :value="$item?->endsAt?->format('Y-m-d\TH:i')" />
    @endif
    <div class="sm:col-span-2">
        <x-form.toggle name="is_active" label="Switched on" :checked="$item?->isActive ?? true" />
    </div>
</x-admin.form-section>
