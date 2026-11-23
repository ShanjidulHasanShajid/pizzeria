{{-- Included by create and edit. $location: LocationView when editing, null when creating. --}}
@use('App\Modules\Content\Domain\Enums\Weekday')
<x-admin.form-section title="Branch">
    <x-form.input name="name" label="Name" :value="$location?->name" required maxlength="100" />
    <x-form.input name="phone" label="Phone" :value="$location?->phone" required maxlength="25" help="A mobile, a landline or a hotline." />
    <x-form.input name="address" label="Address" :value="$location?->address" required maxlength="255" />
    <x-form.input name="area" label="Area" :value="$location?->area" maxlength="100" help="For example Dhanmondi." />
    <x-form.toggle name="is_pickup_available" label="Customers can pick up here" :checked="$location?->isPickupAvailable ?? true" />
    <x-form.toggle name="is_active" label="Switched on" :checked="$location?->isActive ?? true" />
</x-admin.form-section>

<x-admin.form-section title="Map" description="Optional. In Google Maps choose Share, then Embed a map, then Copy HTML.">
    <div class="sm:col-span-2">
        <x-form.textarea name="map_embed_url" label="Map code or address" :value="$location?->mapEmbedUrl" rows="3" help="Only genuine Google Maps embed addresses are accepted." />
    </div>
    <x-form.input name="latitude" label="Latitude" :value="$location?->latitude" help="For example 23.7465" />
    <x-form.input name="longitude" label="Longitude" :value="$location?->longitude" help="For example 90.3760" />
</x-admin.form-section>

<x-admin.form-section title="Opening hours" description="Dhaka time. A closing time at or before the opening time is on the next day: 18:00 to 02:00. 00:00 to 00:00 is around the clock.">
    <div class="space-y-3 sm:col-span-2" x-data="{
        copyFirst() {
            const rows = [...this.$root.querySelectorAll('[data-day]')]
            const field = (row, name) => row.querySelector('[data-field=' + name + ']')
            rows.slice(1).forEach((row) => {
                field(row, 'opens').value = field(rows[0], 'opens').value
                field(row, 'closes').value = field(rows[0], 'closes').value
                field(row, 'open').checked = field(rows[0], 'open').checked
                field(row, 'open').dispatchEvent(new Event('change'))
            })
        },
    }">
        @foreach (Weekday::cases() as $day)
            @php
                $name = "hours.{$day->value}";
                $saved = $location === null ? ['11:00', '23:00'] : $location->hours[$day->value];
                $isOpen = (bool) old("{$name}.is_open", $saved !== null);
                $hasError = $errors->has($name);
            @endphp
            <div data-day x-data="{ open: @js($isOpen) }" role="group" aria-label="{{ $day->label() }}">
                <div class="flex min-h-11 flex-wrap items-center gap-3">
                    <label for="open-{{ $day->value }}" class="inline-flex w-36 items-center gap-2 text-sm font-medium text-ink">
                        <input type="hidden" name="hours[{{ $day->value }}][is_open]" value="0">
                        <input id="open-{{ $day->value }}" data-field="open" type="checkbox" name="hours[{{ $day->value }}][is_open]" value="1" x-model="open" @checked($isOpen)
                            class="h-4 w-4 rounded border-stone-400 text-brand-600 focus:ring-brand-600">
                        {{ $day->label() }}
                    </label>
                    <span x-show="open" class="flex items-center gap-2 text-sm">
                        <input type="time" data-field="opens" name="hours[{{ $day->value }}][opens]" value="{{ old("{$name}.opens", $saved[0] ?? '') }}" aria-label="{{ $day->label() }} opens"
                            @if ($hasError) aria-invalid="true" @endif class="field {{ $hasError ? 'field-invalid' : '' }} w-32">
                        <span>to</span>
                        <input type="time" data-field="closes" name="hours[{{ $day->value }}][closes]" value="{{ old("{$name}.closes", $saved[1] ?? '') }}" aria-label="{{ $day->label() }} closes"
                            @if ($hasError) aria-invalid="true" @endif class="field {{ $hasError ? 'field-invalid' : '' }} w-32">
                    </span>
                    <span x-show="! open" x-cloak class="text-sm text-muted">Closed</span>
                    @if ($loop->first)
                        <x-button size="sm" variant="outline" x-on:click="copyFirst()">Copy to all days</x-button>
                    @endif
                </div>
                <x-form.error :name="$name" />
            </div>
        @endforeach
    </div>
</x-admin.form-section>
