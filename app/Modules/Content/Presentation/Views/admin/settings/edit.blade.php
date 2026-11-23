{{--
    $group: the open SettingGroup. $groups: all of them. $fields: list<SettingField>.
    $imageUrl: stored path => URL (null for an empty path).
--}}
@use('App\Modules\Content\Domain\Enums\SettingType')
@use('App\Modules\Content\Presentation\Support\SettingLabels')
<x-layouts.admin title="Settings">
    <x-admin.page-header title="Settings" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Settings']]" />

    <nav class="mb-6 flex gap-1 overflow-x-auto border-b border-line" aria-label="Settings groups">
        @foreach ($groups as $tab)
            <a href="{{ route('admin.settings.edit', ['tab' => $tab->value]) }}" @if ($tab === $group) aria-current="page" @endif
                class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold {{ $tab === $group ? 'border-brand-600 text-brand-700' : 'border-transparent text-muted hover:text-ink' }}">{{ $tab->label() }}</a>
        @endforeach
    </nav>

    <form method="POST" action="{{ route('admin.settings.update', $group->value) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        <x-admin.form-section :title="$group->label()">
            @foreach ($fields as $field)
                @php
                    $name = $field->key->value;
                    $text = SettingLabels::for($field->key);
                @endphp
                @switch($field->key->type())
                    @case(SettingType::Image)
                        <div class="sm:col-span-2">
                            <x-admin.image-upload :name="$name" :label="$text['label']" :current="$imageUrl($field->value)" :help="$text['help']" />
                        </div>
                        @break

                    @case(SettingType::Boolean)
                        <div class="sm:col-span-2">
                            <x-form.toggle :name="$name" :label="$text['label']" :checked="$field->value === '1'" />
                        </div>
                        @break

                    @case(SettingType::LongText)
                        <div class="sm:col-span-2">
                            <x-form.textarea :name="$name" :label="$text['label']" :value="$field->value" :help="$text['help']" rows="3" maxlength="500" />
                        </div>
                        @break

                    @default
                        <x-form.input :name="$name" :label="$text['label']" :value="$field->value" :help="$text['help']" :required="$field->key->isRequired()" maxlength="255" />
                @endswitch
            @endforeach
        </x-admin.form-section>

        <x-admin.save-bar label="Save settings" />
    </form>
</x-layouts.admin>
