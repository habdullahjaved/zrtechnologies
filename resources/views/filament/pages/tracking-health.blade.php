<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section heading="Platforms" description="Tags only load for visitors who accept cookies.">
            <ul class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($this->platforms() as $platform)
                    <li class="flex items-start justify-between gap-4 py-2.5">
                        <span class="flex items-center gap-2 font-medium">
                            @if ($platform['ok'])
                                <x-filament::icon icon="heroicon-s-check-circle" class="h-5 w-5 text-success-600" />
                            @else
                                <x-filament::icon icon="heroicon-o-minus-circle" class="h-5 w-5 text-gray-400" />
                            @endif
                            {{ $platform['label'] }}
                        </span>
                        <span class="text-right text-sm text-gray-500 dark:text-gray-400">{{ $platform['detail'] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-filament::section>

        <x-filament::section heading="Server-side sends (last 7 days)" description="Full details under Tracking events.">
            @forelse ($this->lastWeek() as $platform => $statuses)
                <div class="py-2">
                    <p class="font-medium">{{ $platform }}</p>
                    <p class="mt-1 flex flex-wrap gap-2">
                        @foreach ($statuses as $status => $total)
                            <x-filament::badge color="gray">{{ $status }}: {{ $total }}</x-filament::badge>
                        @endforeach
                    </p>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Nothing sent yet. Leads, bookings and completed repairs will appear here.</p>
            @endforelse
        </x-filament::section>

        <x-filament::section heading="Cookie choices (last 30 days)">
            @php($consent = $this->consentSummary())
            <div class="grid grid-cols-3 gap-4 text-center">
                <div><p class="text-2xl font-bold">{{ $consent['accepted'] }}</p><p class="text-sm text-gray-500">Accepted all</p></div>
                <div><p class="text-2xl font-bold">{{ $consent['rejected'] }}</p><p class="text-sm text-gray-500">Rejected all</p></div>
                <div><p class="text-2xl font-bold">{{ $consent['custom'] }}</p><p class="text-sm text-gray-500">Chose settings</p></div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Google Ads offline conversions" description="Completed repairs, for a daily scheduled upload in Google Ads (Goals → Conversions → Uploads → Schedules, source HTTPS).">
            <p class="text-sm break-all"><code>{{ $this->offlineExportUrl() }}</code></p>
            <p class="mt-2 text-sm text-gray-500">Keep this link private: it is the only key to the file.</p>
        </x-filament::section>

        <x-filament::section heading="Google Business Profile links" description="Paste these into your profile so visits from it are credited to Google Business Profile." class="lg:col-span-2">
            <ul class="space-y-3">
                @foreach ($this->googleBusinessLinks() as $link)
                    <li>
                        <p class="text-sm font-medium">{{ $link['label'] }}</p>
                        <code class="block text-sm break-all text-gray-600 dark:text-gray-300">{{ $link['url'] }}</code>
                    </li>
                @endforeach
            </ul>
            @unless ($this->reviewLinkSet())
                <p class="mt-4 text-sm text-warning-600">Add your Google review link in Settings → Social so review requests can be sent.</p>
            @endunless
        </x-filament::section>
    </div>
</x-filament-panels::page>
