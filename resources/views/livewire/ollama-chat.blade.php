<div class="h-screen">

    <flux:sidebar sticky stashable
        class="bg-zinc-50 dark:bg-zinc-900 border-r rtl:border-r-0 rtl:border-l border-zinc-200 dark:border-zinc-700">

        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <flux:brand href="/" logo="/images/ollama.png" name="Ollama Chat" class="px-2" />

        <flux:navlist variant="outline">
            <flux:navlist.item icon="plus" href="/" current>
                New Chat
            </flux:navlist.item>
            <flux:navlist.group expandable heading="Recent Chats" class="grid mt-4">
                @foreach ($chats as $chat)
                    <flux:navlist.item class="flex items-center gap-2"
                        wire:click="loadChat('{{ $chat['meta']['fileName'] }}')">
                        <flux:tooltip content="Started on {{ date('d/m/y H:i', $chat['meta']['created_at']) }}">
                            <flux:icon name="check" class="inline-block size-4" />
                        </flux:tooltip>
                        <span>
                            {{ $chat['title'] }}
                        </span>
                    </flux:navlist.item>
                @endforeach
            </flux:navlist.group>
        </flux:navlist>

        <flux:spacer />

        <div>
            <flux:modal.trigger name="chat-settings" class="hidden lg:block">
                <flux:button icon="cog-6-tooth" variant="ghost">Settings</flux:button>
            </flux:modal.trigger>

            <flux:button icon="x-mark" wire:click="clearChat" variant="ghost">
                Clear Chat
            </flux:button>
        </div>
    </flux:sidebar>

    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:spacer />
        <flux:modal.trigger name="chat-settings">
            <flux:button icon="cog-6-tooth" variant="subtle">
                <span class="sr-only">Settings</span>
            </flux:button>
        </flux:modal.trigger>
        <flux:button icon="trash" wire:click="clearChat" variant="subtle">
            <span class="sr-only">Clear Chat</span>
        </flux:button>
    </flux:header>

    <flux:main>

        <section class="h-full flex flex-col">
            <div class="flex-1 overflow-y-auto mb-4 space-y-4">
                @foreach ($messages as $message)
                    <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                        <section @class([
                            'max-w-[80%] rounded-lg p-4',
                            'bg-gray-50 dark:bg-zinc-600' => $message['role'] === 'user',
                            'bg-gray-100 dark:bg-zinc-900' => $message['role'] === 'assistant',
                        ]) <span>{{ $message['content'] }}</span>
                        </section>
                    </div>
                @endforeach

                @if ($isLoading)
                    <div class="flex items-center gap-2 p-4 bg-gray-100 dark:bg-zinc-900 rounded-lg">
                        <flux:icon name="loading" class="animate-spin h-4 w-4" />
                        <span>Thinking...</span>
                    </div>
                @endif
            </div>

            <div class="flex flex-col gap-2">
                <flux:textarea wire:model="input" placeholder="Type your message..." class="flex-1" />

                <flux:button wire:click="sendMessage" :loading="$isLoading" variant="primary"
                    class="max-w-64 self-end">
                    Send
                </flux:button>
            </div>
        </section>

        <flux:modal name="chat-settings" class="md:w-96">
            <div class="space-y-6">
                <flux:field>
                    <flux:label for="model">Select Model</flux:label>
                    <flux:select wire:model.live="selectedModel" id="model" option-label="name" option-value="name">
                        @foreach ($availableModels as $model)
                            <flux:select.option value="{{ $model['name'] }}">{{ $model['name'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:description>Select the model you want to use.</flux:description>
                </flux:field>

                <flux:field class="mt-6">
                    <flux:label for="agent">Select Agent</flux:label>
                    <flux:textarea wire:model.live="agent" id="agent" placeholder="Enter agent prompt..." />
                    <flux:description>Enter the agent prompt you want to use.</flux:description>
                </flux:field>
            </div>
        </flux:modal>
    </flux:main>
</div>
