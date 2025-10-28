{{-- About Page Template --}}
<x-theme::layouts.main>
    <x-slot:main>
        {{-- Page Header with Electric Storm --}}
        <section class="relative overflow-hidden bg-gradient-to-br from-black via-slate-950 to-slate-900 py-20 text-white">
            {{-- Electric Orbs --}}
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -left-20 top-20 h-96 w-96 rounded-full bg-gradient-to-r from-emerald-500/20 to-lime-500/20 blur-3xl"></div>
                <div class="absolute -right-20 bottom-20 h-96 w-96 rounded-full bg-gradient-to-r from-cyan-500/20 to-emerald-500/20 blur-3xl"></div>
            </div>

            <div class="container relative mx-auto px-4">
                <div class="mx-auto max-w-4xl text-center">
                    <h1 class="mb-6 bg-gradient-to-r from-lime-400 via-emerald-400 to-cyan-400 bg-clip-text text-4xl font-bold text-transparent drop-shadow-[0_0_20px_rgba(163,230,53,0.5)] md:text-5xl lg:text-6xl">
                        @cmsField(
                            'header.title',
                            'text',
                            [
                                'label' => 'Page Title',
                                'default' => 'About FrankenCMS',
                                'maxLength' => 100,
                            ]
                        )
                    </h1>

                    <p class="text-xl text-emerald-200/90">
                        @cmsField(
                            'header.subtitle',
                            'text',
                            [
                                'label' => 'Page Subtitle',
                                'default' => 'Building the future of content management, one feature at a time',
                                'maxLength' => 200,
                            ]
                        )
                    </p>
                </div>
            </div>
        </section>

        {{-- Mission Section --}}
        <section class="bg-slate-950 py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-4xl">
                    <h2 class="mb-6 bg-gradient-to-r from-lime-400 to-emerald-400 bg-clip-text text-center text-3xl font-bold text-transparent drop-shadow-[0_0_15px_rgba(163,230,53,0.4)]">
                        @cmsField(
                            'mission.title',
                            'text',
                            [
                                'label' => 'Mission Title',
                                'default' => 'Our Mission',
                                'maxLength' => 100,
                            ]
                        )
                    </h2>

                    <div class="prose prose-lg prose-invert mx-auto prose-headings:bg-gradient-to-r prose-headings:from-lime-400 prose-headings:to-emerald-400 prose-headings:bg-clip-text prose-headings:text-transparent prose-p:text-emerald-200/80 prose-strong:text-lime-400 prose-a:text-cyan-400 hover:prose-a:text-cyan-300">
                        @cmsField(
                            'mission.content',
                            'richEditor',
                            [
                                'label' => 'Mission Content',
                                'default' => '<p>FrankenCMS is a modern content management system built on Laravel 12 and FilamentPHP. We believe that managing content should be intuitive, powerful, and enjoyable.</p><p>Our mission is to provide developers and content creators with a CMS that combines the flexibility of Laravel with the elegance of FilamentPHP, creating an unmatched content management experience.</p>',
                            ]
                        )
                    </div>
                </div>
            </div>
        </section>

        {{-- Story Section --}}
        <section class="bg-slate-900 py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-4xl">
                    <h2 class="mb-6 bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-center text-3xl font-bold text-transparent drop-shadow-[0_0_15px_rgba(52,211,153,0.4)]">
                        @cmsField(
                            'story.title',
                            'text',
                            [
                                'label' => 'Story Title',
                                'default' => 'Our Story',
                                'maxLength' => 100,
                            ]
                        )
                    </h2>

                    <div class="prose prose-lg prose-invert mx-auto prose-headings:bg-gradient-to-r prose-headings:from-lime-400 prose-headings:to-emerald-400 prose-headings:bg-clip-text prose-headings:text-transparent prose-p:text-lime-100/80 prose-strong:text-emerald-400 prose-a:text-cyan-400 hover:prose-a:text-cyan-300">
                        @cmsField(
                            'story.content',
                            'richEditor',
                            [
                                'label' => 'Story Content',
                                'default' => '<p>FrankenCMS was born from a simple idea: content management doesn\'t have to be complicated. We saw developers struggling with bloated CMSs that were either too restrictive or too complex.</p><p>By combining Laravel\'s robust framework with FilamentPHP\'s beautiful admin interface, we created a CMS that\'s both powerful and delightful to use. Our custom field directive system eliminates the need for complex custom field configurations, making content management truly intuitive.</p>',
                            ]
                        )
                    </div>
                </div>
            </div>
        </section>

        {{-- Values Section --}}
        <section class="bg-slate-950 py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-6xl">
                    <h2 class="mb-12 bg-gradient-to-r from-lime-400 via-emerald-400 to-cyan-400 bg-clip-text text-center text-3xl font-bold text-transparent drop-shadow-[0_0_15px_rgba(163,230,53,0.4)]">
                        @cmsField(
                            'values.title',
                            'text',
                            [
                                'label' => 'Values Section Title',
                                'default' => 'Our Values',
                                'maxLength' => 100,
                            ]
                        )
                    </h2>

                    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                        @cmsField(
                            'values.items',
                            'repeater',
                            [
                                'label' => 'Values',
                                'schema' => [
                                    ['name' => 'icon', 'type' => 'text', 'label' => 'Icon (emoji)', 'default' => '🎯'],
                                    ['name' => 'title', 'type' => 'text', 'label' => 'Title', 'required' => true],
                                    ['name' => 'description', 'type' => 'textarea', 'label' => 'Description', 'rows' => 3],
                                ],
                                'defaultItems' => 6,
                                'collapsible' => true,
                                'itemLabel' => fn ($state) => $state['title'] ?? 'Value',
                            ]
                        )

                        @foreach ($cmsFields['valuesItems'] ?? [] as $value)
                            <div class="group text-center">
                                <div class="mb-4 flex justify-center">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-lime-400 to-emerald-500 text-3xl shadow-[0_0_20px_rgba(163,230,53,0.5)] ring-2 ring-emerald-400/30 transition-all duration-300 group-hover:scale-110 group-hover:shadow-[0_0_30px_rgba(163,230,53,0.7)]">
                                        {{ $value['custom_fields']['icon'] ?? '🎯' }}
                                    </div>
                                </div>
                                <h3 class="mb-3 bg-gradient-to-r from-lime-400 to-emerald-400 bg-clip-text text-xl font-bold text-transparent">{{ $value['custom_fields']['title'] }}</h3>
                                <p class="text-emerald-200/70">{{ $value['custom_fields']['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Team Section --}}
        <section class="bg-slate-900 py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-6xl">
                    <h2 class="mb-4 bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-center text-3xl font-bold text-transparent drop-shadow-[0_0_15px_rgba(52,211,153,0.4)]">
                        @cmsField(
                            'team.title',
                            'text',
                            [
                                'label' => 'Team Section Title',
                                'default' => 'Meet the Team',
                                'maxLength' => 100,
                            ]
                        )
                    </h2>

                    <p class="mb-12 text-center text-emerald-200/70">
                        @cmsField(
                            'team.subtitle',
                            'text',
                            [
                                'label' => 'Team Section Subtitle',
                                'default' => 'The people behind FrankenCMS',
                                'maxLength' => 200,
                            ]
                        )
                    </p>

                    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                        @cmsField(
                            'team.members',
                            'repeater',
                            [
                                'label' => 'Team Members',
                                'schema' => [
                                    ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
                                    ['name' => 'role', 'type' => 'text', 'label' => 'Role', 'required' => true],
                                    ['name' => 'twitter', 'type' => 'url', 'label' => 'Twitter URL'],
                                    ['name' => 'github', 'type' => 'url', 'label' => 'GitHub URL'],
                                ],
                                'defaultItems' => 4,
                                'collapsible' => true,
                                'itemLabel' => fn ($state) => $state['name'] ?? 'Team Member',
                            ]
                        )

                        @foreach ($cmsFields['teamMembers'] ?? [] as $member)
                            <div class="text-center">
                                <div class="mb-4 flex justify-center">
                                    <div class="h-32 w-32 overflow-hidden rounded-full bg-gradient-to-br from-slate-800 to-slate-700 ring-2 ring-emerald-500/30 shadow-[0_0_20px_rgba(163,230,53,0.3)]"></div>
                                </div>
                                <h3 class="mb-1 text-lg font-bold text-lime-100">{{ $member['custom_fields']['name'] }}</h3>
                                <p class="mb-2 text-sm text-emerald-200/70">{{ $member['custom_fields']['role'] }}</p>
                                <div class="flex justify-center gap-2">
                                    @if (!empty($member['custom_fields']['twitter']))
                                        <a href="{{ $member['custom_fields']['twitter'] }}" class="text-lime-400 transition-all duration-300 hover:scale-110 hover:text-cyan-400 hover:drop-shadow-[0_0_8px_rgba(6,182,212,0.8)]">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                                            </svg>
                                        </a>
                                    @endif
                                    @if (!empty($member['custom_fields']['github']))
                                        <a href="{{ $member['custom_fields']['github'] }}" class="text-lime-400 transition-all duration-300 hover:scale-110 hover:text-cyan-400 hover:drop-shadow-[0_0_8px_rgba(6,182,212,0.8)]">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA Section with Electric Storm --}}
        <section class="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-slate-900 to-cyan-900 py-16 text-white">
            {{-- Electric Energy Background --}}
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute left-1/4 top-0 h-96 w-96 rounded-full bg-gradient-to-r from-lime-500/20 to-emerald-500/20 blur-3xl"></div>
                <div class="absolute bottom-0 right-1/4 h-96 w-96 rounded-full bg-gradient-to-r from-cyan-500/20 to-lime-500/20 blur-3xl"></div>
            </div>

            <div class="container relative mx-auto px-4">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="mb-4 bg-gradient-to-r from-lime-400 via-emerald-400 to-cyan-400 bg-clip-text text-3xl font-bold text-transparent drop-shadow-[0_0_20px_rgba(163,230,53,0.5)]">
                        @cmsField(
                            'cta.title',
                            'text',
                            [
                                'label' => 'CTA Title',
                                'default' => 'Want to join our team?',
                                'maxLength' => 100,
                            ]
                        )
                    </h2>

                    <p class="mb-8 text-lg text-emerald-200/90">
                        @cmsField(
                            'cta.description',
                            'textarea',
                            [
                                'label' => 'CTA Description',
                                'default' => 'We\'re always looking for talented people who share our passion for building great software.',
                                'rows' => 2,
                            ]
                        )
                    </p>

                    <a
                        href="@cmsField('cta.button_url', 'url', ['label' => 'CTA Button URL', 'default' => '/contact'])"
                        class="inline-block rounded-lg bg-gradient-to-r from-lime-400 to-emerald-500 px-8 py-3 font-semibold text-slate-900 shadow-[0_0_20px_rgba(163,230,53,0.5)] transition-all duration-300 hover:scale-105 hover:shadow-[0_0_30px_rgba(163,230,53,0.8)]"
                    >
                        @cmsField('cta.button_text', 'text', ['label' => 'CTA Button Text', 'default' => 'Get in Touch'])
                    </a>
                </div>
            </div>
        </section>
    </x-slot>
</x-theme::layouts.main>
