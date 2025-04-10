# Llama Chat

A simple command-line and user interface for interacting with local [Ollama](https://ollama.com) models.

## Prerequisites

To use this application, ensure the following:

1. [Ollama](https://github.com/ollama/ollama) is installed.
2. At least one model is downloaded. Refer to the [Ollama documentation](https://github.com/ollama/ollama) for guidance.

---

## Features

- **Command-Line Interface (CLI):** Interact with AI models directly from the terminal.
- **User Interface (UI):** A simple web-based interface for chatting with models.
- **Customizable Options:** Choose models, agents, and response formats.

---

## To-Do List

- [ ] Add proper syntax highlighting
- [ ] Improve assistant answer styles
- [ ] Send message on CTRL/CMD + ENTER
- [ ] Add search functionality

---

## User Interface (UI)

![llama-chat-screenshot](https://raw.githubusercontent.com/levonium/llama-chat/refs/heads/main/public/images/llama-chat.jpg)

1. Start the server by running:

```bash
  php artisan serve
```

2. Visit the application at `http://127.0.0.1:8000` in your browser.
3. Start chatting with your preferred model.

---

## Command-Line Interface (CLI)

### Available Commands

#### `llama:chat`

Start a chat session with an AI model.

```bash
php artisan llama:chat --model=llama3.2 --agent="You are a helpful assistant." --i --f
```

**Options:**

- `--model` - Specify the model to use (default: `llama3.2`).
- `--agent` - Define the agent's description (default: `You are a helpful assistant.`).
- `--i` - Enable interactive mode (default: `false`).
- `--f` - Return formatted responses (default: `false`).

#### `llama:ask`

Ask a one-off question to an AI model.

```bash
php artisan llama:ask --model=llama3.2 --agent="You are a helpful assistant."
```

**Options:**

- `--model` - Specify the model to use (default: `llama3.2`).
- `--agent` - Define the agent's description (default: `You are a helpful assistant.`).

#### `llama:model:list`

List all available (downloaded) models.

```bash
php artisan llama:model:list
```

#### `llama:model:show`

Show detailed information about a specific model.

```bash
php artisan llama:model:show --model=<model_name>
```

**Options:**

- `--model` - Specify the model name. If omitted, you will be prompted to enter it.

---

## Installation

Follow these steps to set up the application:

1. Clone the repository:

    ```bash
    git clone [repository]
    ```

2. Navigate to the project directory:

    ```bash
    cd llama-chat
    ```

3. Copy the environment file:

    ```bash
    cp .env.example .env
    ```

4. Generate the application key:

    ```bash
    php artisan key:generate
    ```

5. Start the server:

    ```bash
    php artisan serve
    ```

6. Visit `http://127.0.0.1:8000` in your browser.

---

## Logs

Access application logs by visiting:

```
http://127.0.0.1:8000/llogs
```
