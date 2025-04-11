<div class="h-screen">

    <flux:sidebar sticky
                  stashable
                  class="border-r border-zinc-200 bg-zinc-50 rtl:border-l rtl:border-r-0 dark:border-zinc-700 dark:bg-zinc-900">

        <flux:sidebar.toggle class="lg:hidden"
                             icon="x-mark" />

        <flux:brand href="/"
                    logo="/favicon.svg"
                    name="Llama Chat"
                    class="px-2" />

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
                        <span class="{{ $chat['meta']['fileName'] === $fileName ? 'font-bold underline' : '' }}">
                            {{ $chat['title'] }}
                        </span>
                    </flux:navlist.item>
                @empty
                    <flux:navlist.item disabled>
                        No recent chats
                    </flux:navlist.item>
                @endforelse
            </flux:navlist.group>

            <flux:navlist.group expandable
                                heading="Uploaded Files"
                                class="mt-4 grid">
                @forelse ($uploads as $file)
                    <flux:navlist.item wire:click="viewFile('{{ $file['name'] }}')"
                                       class="group flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:icon name="document"
                                       class="size-4" />
                            <span class="truncate"
                                  title="{{ $file['name'] }}">
                                {{ $file['name'] }}
                            </span>
                        </div>
                    </flux:navlist.item>
                @empty
                    <flux:navlist.item disabled>
                        No uploaded files
                    </flux:navlist.item>
                @endforelse
            </flux:navlist.group>
        </flux:navlist>

        <flux:spacer />

        <div>
            <flux:modal.trigger name="help"
                                class="hidden lg:block">
                <flux:button icon="information-circle"
                             variant="ghost">Help</flux:button>
            </flux:modal.trigger>

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
                          class="flex h-full items-center justify-center text-center text-4xl text-purple-200 lg:text-6xl dark:text-purple-900">
                        Start a new chat
                        <svg xmlns="http://www.w3.org/2000/svg"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             class="ml-2 size-12 text-purple-300 lg:size-24 dark:text-purple-700">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                        </svg>
                    </span>
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
                               class="flex-1"
                               autofocus />

                <div class="mt-2 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <flux:button @click="$refs.fileInput.click()"
                                     :disabled="$isLoading"
                                     variant="filled"
                                     icon="document-arrow-up"
                                     size="sm"
                                     x-data>
                            <input type="file"
                                   wire:model="uploadedFile"
                                   wire:loading.attr="disabled"
                                   class="hidden"
                                   x-ref="fileInput">
                            Attach File
                        </flux:button>
                        @error('uploadedFile')
                            <div class="text-sm text-red-500 dark:bg-red-900/20">
                                {!! nl2br($message) !!}
                            </div>
                        @enderror
                    </div>

                    <flux:button wire:click="sendMessage"
                                 :disabled="$isLoading"
                                 variant="primary"
                                 icon="arrow-up"
                                 size="sm">
                        Send
                    </flux:button>
                </div>
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
                            <flux:select.option value="{{ $model['name'] }}">{{ $model['name'] }}
                            </flux:select.option>
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

        <flux:modal name="help">
            <div class="chat-message">
                {!! str(file_get_contents(resource_path('views/help.blade.php')))->markdown() !!}
            </div>
        </flux:modal>

        <flux:modal name="file-modal"
                    wire:close="closeFileModal">
            <div class="space-y-4">
                <h3 class="text-lg font-semibold">{{ $viewingFile }}</h3>

                @if ($fileContent)
                    <div class="max-h-[60vh] overflow-auto rounded-lg bg-gray-50 p-4 dark:bg-zinc-900">
                        @if (in_array($fileContent['type'], ['text/plain', 'text/markdown', 'text/html']))
                            <pre class="whitespace-pre-wrap font-mono text-sm">{{ $fileContent['content'] }}</pre>
                        @elseif ($fileContent['type'] === 'application/json')
                            <pre class="whitespace-pre-wrap font-mono text-sm">{{ json_encode(json_decode($fileContent['content']), JSON_PRETTY_PRINT) }}</pre>
                        @else
                            <pre class="whitespace-pre-wrap font-mono text-sm">{{ $fileContent['content'] }}</pre>
                        @endif
                    </div>
                @endif
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
        $wire.on('file-selected', () => Flux.modal('file-modal').show())
    </script>
@endscript
