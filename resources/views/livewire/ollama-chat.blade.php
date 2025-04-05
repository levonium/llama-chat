<div class="h-screen">

    <flux:sidebar sticky
                  stashable
                  class="border-r border-zinc-200 bg-zinc-50 rtl:border-l rtl:border-r-0 dark:border-zinc-700 dark:bg-zinc-900">

        <flux:sidebar.toggle class="lg:hidden"
                             icon="x-mark" />

        <flux:brand href="/"
                    logo="/images/ollama.png"
                    name="Ollama Chat"
                    class="px-2 dark:hidden" />

        <flux:brand href="/"
                    logo="/favicon.svg"
                    name="Ollama Chat"
                    class="hidden px-2 dark:flex" />

        <flux:input variant="filled"
                    placeholder="Search..."
                    icon="magnifying-glass" />

        <flux:navlist variant="outline">
            <flux:navlist.item icon="plus"
                               href="/"
                               current>
                New Chat
            </flux:navlist.item>

            <flux:navlist.group expandable
                                heading="Recent Chats"
                                class="mt-4 grid">
                @forelse ($chats as $chat)
                    <flux:navlist.item wire:click="loadChat('{{ $chat['meta']['fileName'] }}')">
                        <flux:tooltip content="Started on {{ date('d/m/y H:i', $chat['meta']['created_at']) }}">
                            <flux:icon name="check"
                                       class="inline-block size-4" />
                        </flux:tooltip>
                        <span data-file="{{ $chat['meta']['fileName'] }}"
                              data-selected="{{ $fileName }}"
                              class="{{ $chat['meta']['fileName'] === $fileName ? 'font-bold underline' : '' }}">
                            {{ $chat['title'] }}
                        </span>
                    </flux:navlist.item>
                @empty
                    <flux:navlist.item disabled>
                        No recent chats
                    </flux:navlist.item>
                @endforelse
            </flux:navlist.group>
        </flux:navlist>

        <flux:spacer />

        <div>
            <flux:modal.trigger name="chat-settings"
                                class="hidden lg:block">
                <flux:button icon="cog-6-tooth"
                             variant="ghost">Settings</flux:button>
            </flux:modal.trigger>

            <flux:button icon="x-mark"
                         wire:click="clearChat"
                         variant="ghost">
                Clear Chat
            </flux:button>
        </div>
    </flux:sidebar>

    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden"
                             icon="bars-2"
                             inset="left" />
        <flux:spacer />
        <flux:modal.trigger name="chat-settings">
            <flux:button icon="cog-6-tooth"
                         variant="subtle">
                <span class="sr-only">Settings</span>
            </flux:button>
        </flux:modal.trigger>
        <flux:button icon="trash"
                     wire:click="clearChat"
                     variant="subtle">
            <span class="sr-only">Clear Chat</span>
        </flux:button>
    </flux:header>

    <flux:main class="lg:h-screen">

        <section class="flex h-full flex-col">
            <div class="chats mb-4 flex-1 space-y-4 overflow-y-auto">
                @forelse ($messages as $message)
                    <div class="{{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }} flex">
                        <section @class([
                            'chat-message max-w-[80%] rounded-lg p-4',
                            'bg-gray-50 dark:bg-zinc-600' => $message['role'] === 'user',
                            'bg-gray-100 dark:bg-zinc-900' => $message['role'] === 'assistant',
                        ])
                                 <span>{!! $message['content'] !!}</span>
                        </section>
                    </div>
                @empty
                    <span
                          class="flex h-full items-center justify-center text-center text-4xl font-bold text-white/40 lg:text-6xl">Start
                        a new chat</span>
                @endforelse

                @if ($isLoading)
                    <div class="chat-loading flex items-center gap-2 rounded-lg bg-gray-100 p-4 dark:bg-zinc-900">
                        <flux:icon name="loading"
                                   class="h-4 w-4 animate-spin" />
                        <span>Thinking...</span>
                    </div>
                @endif
            </div>

            <div class="relative">
                <flux:textarea rows="2"
                               wire:model="input"
                               placeholder="Type your message..."
                               class="flex-1" />

                <flux:button wire:click="sendMessage"
                             :disabled="$isLoading"
                             variant="primary"
                             icon="arrow-up"
                             size="sm"
                             class="!absolute bottom-2 right-2">
                </flux:button>
            </div>
        </section>

        <flux:modal name="chat-settings"
                    class="md:w-96">
            <div class="space-y-6">
                <flux:field>
                    <flux:label for="model">Select Model</flux:label>
                    <flux:select wire:model.live="selectedModel"
                                 id="model"
                                 option-label="name"
                                 option-value="name">
                        @foreach ($availableModels as $model)
                            <flux:select.option value="{{ $model['name'] }}">{{ $model['name'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:description>Select the model you want to use.</flux:description>
                </flux:field>

                <flux:field class="mt-6">
                    <flux:label for="agent">Select Agent</flux:label>
                    <flux:textarea wire:model.live="agent"
                                   id="agent"
                                   placeholder="Enter agent prompt..." />
                    <flux:description>Enter the agent prompt you want to use.</flux:description>
                </flux:field>
            </div>
        </flux:modal>
    </flux:main>
</div>

@script
    <script>
        const scrollToBottom = () => {
            if (document.querySelector('.chat-loading')) {
                document.querySelector('.chat-loading').scrollIntoView({
                    behavior: 'smooth'
                })
            } else {
                document.querySelector('.chats').scrollTo({
                    top: document.querySelector('.chats').scrollHeight,
                    behavior: 'smooth'
                })
            }
        }
        $wire.on('chat-selected', () => setTimeout(() => scrollToBottom()))
        $wire.on('message-added', () => setTimeout(() => scrollToBottom(), 1000))
    </script>
@endscript
