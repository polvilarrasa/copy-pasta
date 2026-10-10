<x-layouts::public :title="__('ui.showcase.title')">
    @php
        $sampleUser = \App\Models\User::factory()->make(['id' => 7, 'username' => 'lola_exe']);
        $sampleBody = "Querido router:\nSé que no hablamos mucho, pero siempre estás ahí, parpadeando.\nHoy me has dejado sin wifi justo cuando iba a ganar la partida.\nDame una señal.\nCualquiera.";
        $asciiBody = "/\\_/\\\n( o.o )\n > ^ <";
        $tagSamples = [
            ['name' => 'humor', 'color' => 't2'],
            ['name' => 'oficina', 'color' => 't4'],
            ['name' => 'tecnología', 'color' => 't3'],
            ['name' => 'wholesome', 'color' => 't1'],
            ['name' => 'plantillas', 'color' => 't5'],
        ];
    @endphp

    @foreach (['light', 'dark'] as $mode)
        <section
            data-theme="{{ $mode }}"
            x-data="{ modalId: '{{ 'demo-modal-'.$mode }}', toastMessage: '{{ __('ui.showcase.toast_message') }}' }"
            class="bg-bg px-4 py-8 text-ink md:px-8"
            aria-labelledby="showcase-{{ $mode }}"
        >
            <h1 id="showcase-{{ $mode }}" class="text-2xl text-ink">{{ __('ui.showcase.title') }} · {{ __('ui.showcase.'.$mode) }}</h1>

            <div class="mt-8 grid gap-10">
                <div class="grid gap-3">
                    <h2 class="text-lg text-ink">{{ __('ui.showcase.buttons') }}</h2>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.button variant="primary">{{ __('ui.showcase.button.copy') }}</x-ui.button>
                        <x-ui.button variant="secondary">{{ __('ui.showcase.button.save') }}</x-ui.button>
                        <x-ui.button variant="ghost">{{ __('ui.showcase.button.cancel') }}</x-ui.button>
                        <x-ui.button variant="danger">{{ __('ui.showcase.button.delete') }}</x-ui.button>
                        <x-ui.button variant="primary" size="lg">{{ __('ui.showcase.button.publish') }}</x-ui.button>
                        <x-ui.button variant="secondary" size="sm">{{ __('ui.showcase.button.small') }}</x-ui.button>
                    </div>
                </div>

                <div class="grid max-w-md gap-5">
                    <h2 class="text-lg text-ink">{{ __('ui.showcase.fields') }}</h2>
                    <x-ui.input :name="'username-'.$mode" :label="__('ui.showcase.username')" :hint="__('ui.showcase.username_hint')" />
                    <x-ui.textarea :name="'body-'.$mode" :label="__('ui.showcase.body')" :hint="__('ui.showcase.body_hint')" />
                    <x-ui.select :name="'sort-'.$mode" :label="__('ui.showcase.sort')">
                        @foreach (__('ui.showcase.sort_options') as $option)
                            <option>{{ $option }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.checkbox :name="'nsfw-'.$mode" :label="__('ui.showcase.nsfw')" />
                    <x-ui.switch :name="'notify-'.$mode" :label="__('ui.showcase.notify')" checked />
                    <x-ui.otp :name="'code-'.$mode" :label="__('ui.showcase.code')" />
                    <x-ui.password-input :name="'password-'.$mode" :label="__('ui.showcase.password')" autocomplete="new-password" />
                </div>

                <div class="grid gap-3">
                    <h2 class="text-lg text-ink">{{ __('ui.showcase.overlays') }}</h2>
                    <div class="flex flex-wrap items-start gap-3">
                        <x-ui.button x-on:click="$dispatch('open-modal', modalId)">{{ __('ui.showcase.open_modal') }}</x-ui.button>
                        <x-ui.dropdown :id="'demo-dropdown-'.$mode" :label="__('ui.showcase.menu')">
                            <x-slot:trigger>
                                <x-ui.button variant="secondary">{{ __('ui.showcase.menu') }}</x-ui.button>
                            </x-slot:trigger>
                            <x-ui.menu-item>{{ __('ui.showcase.menu_profile') }}</x-ui.menu-item>
                            <x-ui.menu-item>{{ __('ui.showcase.menu_settings') }}</x-ui.menu-item>
                        </x-ui.dropdown>
                        <x-ui.button variant="secondary" x-on:click="$dispatch('ui-toast', { message: toastMessage })">{{ __('ui.showcase.toast') }}</x-ui.button>
                    </div>

                    <x-ui.modal :id="'demo-modal-'.$mode" :title="__('ui.showcase.modal_title')">
                        {{ __('ui.showcase.modal_body') }}
                        <x-slot:actions>
                            <x-ui.button variant="primary">{{ __('ui.showcase.button.accept') }}</x-ui.button>
                        </x-slot:actions>
                    </x-ui.modal>

                    <x-ui.tabs
                        :id="'tabs-'.$mode"
                        :label="__('ui.showcase.tabs')"
                        :tabs="[['id' => 'top', 'label' => __('ui.showcase.tab_top')], ['id' => 'new', 'label' => __('ui.showcase.tab_new')]]"
                    >
                        <x-ui.tab-panel tab="top" :prefix="'tabs-'.$mode">{{ __('ui.showcase.tab_panel_top') }}</x-ui.tab-panel>
                        <x-ui.tab-panel tab="new" :prefix="'tabs-'.$mode">{{ __('ui.showcase.tab_panel_new') }}</x-ui.tab-panel>
                    </x-ui.tabs>
                </div>

                <div class="grid gap-3">
                    <h2 class="text-lg text-ink">{{ __('ui.showcase.identity') }}</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tagSamples as $tag)
                            <x-ui.tag-chip :name="$tag['name']" :color="$tag['color']" />
                        @endforeach
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <x-avatar :user="$sampleUser" />
                        <x-ui.user-menu :user="$sampleUser">
                            <x-ui.menu-item>{{ __('ui.showcase.menu_profile') }}</x-ui.menu-item>
                            <x-ui.menu-item>{{ __('ui.showcase.sign_out') }}</x-ui.menu-item>
                        </x-ui.user-menu>
                    </div>
                </div>

                <div class="grid gap-3">
                    <h2 class="text-lg text-ink">{{ __('ui.showcase.states') }}</h2>
                    <x-ui.empty-state :title="__('ui.showcase.empty_title')">{{ __('ui.showcase.empty_body') }}</x-ui.empty-state>
                    <x-ui.skeleton class="h-20 w-full" />
                </div>

                <div class="grid gap-4">
                    <h2 class="text-lg text-ink">{{ __('ui.showcase.cards') }}</h2>
                    <div class="grid max-w-md gap-4">
                        <x-ui.copypasta-card
                            title="Carta de amor a mi router"
                            :body="$sampleBody"
                            author="lola.exe"
                            :author-hue="200"
                            achievement="Primera de la clase"
                            :score="1800"
                            :tags="[['name' => 'humor', 'color' => 't2'], ['name' => 'tecnología', 'color' => 't3']]"
                            :reason="['name' => 'humor', 'color' => 't2']"
                        />
                        <x-ui.copypasta-card
                            :title="__('ui.showcase.card.apology_title')"
                            :body="$sampleBody"
                            author="pepe_99"
                            :author-hue="30"
                            :score="42"
                            :my-vote="1"
                            :saved="true"
                            :tags="[['name' => 'oficina', 'color' => 't4']]"
                            :nsfw="true"
                        />
                        <x-ui.copypasta-card
                            :title="__('ui.showcase.card.excuse_title')"
                            :body="$sampleBody"
                            author="ana"
                            :author-hue="330"
                            :score="7"
                            :template="true"
                            :tags="[['name' => 'plantillas', 'color' => 't5']]"
                        />
                        <x-ui.copypasta-card
                            :title="__('ui.showcase.card.ascii_title')"
                            :body="$asciiBody"
                            author="gatito"
                            :author-hue="120"
                            :score="310"
                            :ascii="true"
                            :tags="[['name' => 'wholesome', 'color' => 't1']]"
                        />
                        <x-ui.copypasta-card
                            :title="__('ui.showcase.card.featured_title')"
                            :body="$sampleBody"
                            author="lola.exe"
                            :author-hue="200"
                            :score="1800"
                            :featured="true"
                            featured-date="5 oct"
                            :tags="[['name' => 'humor', 'color' => 't2']]"
                        />
                    </div>
                </div>
            </div>
        </section>
    @endforeach

    <x-ui.toast />
</x-layouts::public>
