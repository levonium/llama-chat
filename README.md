# LLAMA-CHAT

A command line and simple user interface for interacting with local [ollama](https://ollama.com) models.

To use the interfaces provided by this application, please make sure that you already have [Ollama](https://github.com/ollama/ollama) installed and you have downloaded at least one model. Refer to Ollama documentation .

## To-do

- [x] Highlight the selected "recent chat"
- [x] Enable continuing older conversations
- [x] Scroll to the bottom of chat messages automatically
- [ ] Add proper syntax highlighting
- [ ] Improve assistant answer styles
- [ ] Send message on CTRL/CMD + ENTER
- [ ] Add search
- [ ] Add recent chats to CLI option

## UI

Simply visit the homepage of the installed application and start chatting.

One option to start a server is to run `php artisan serve`, then visit `http://127.0.0.1:8000` and start chatting with your preferred model.

## CLI

Available commands:

`ollama:chat`

Use `ollama:chat` command to start a chat with an AI model.

```bash
php artisan ollama:chat --model=llama3.2 --agent="You are a helpful assistant." --i --f

--model - choose the model you want to chat with, default `llama3.2`
--agent - the agent description, default `You are a helpful assistant.`
--i - interactive mode in which case you will be promoted for model and agent. Default `false`
--f - whether to return formatted or raw responses. If `--f` option is present, the responses will be formatted after being displayed, which will disable the option to see the previous responses. Default `false`

# All command options are optional.
```

`ollama:ask`

Use `ollama:ask` command to ask one-off questions.

```bash
php artisan ollama:ask --model=llama3.2 --agent="You are a helpful assistant."

--model - choose the model you want to chat with, default `llama3.2`
--agent - the agent description, default `You are a helpful assistant.`

# All command options are optional.
```

#### `ollama:model:list`

List all available (downloaded) models.

```bash
php artisan ollama:model:list
```

#### `ollama:model:show`

Show model information.

```bash
php artisan ollama:model:show --model

--model - the model. You skip the model option, but you will be prompted to enter its name right after that.
```

## Installation

Run the following commands in the terminal:

1. `git clone [repository]` - this will clone the repository
2. `cd llama-chat` - change the current directory to application's root folder
3. `cp .env.example .env` - copy the environment file
4. `php artisan key:generate` - generate an application key

That will do it, now you should be able to serve the application.

Run `php artisan serve` and visit `http://127.0.0.1:8000`.

## Logs

Visit `/llogs` for application logs.
