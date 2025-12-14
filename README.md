# OpenRouter Chat for WordPress

A lightweight WordPress plugin that integrates the [OpenRouter API](https://openrouter.ai/) into your website. This plugin allows you to embed an AI chat interface—powered by models like GPT-4, Claude 3, or Llama 3—directly into your WordPress posts or pages using a simple shortcode.

## ⚠️ Disclaimer

**THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND.**
Please see the [License](#-license) section below for details. By using this plugin, you agree that the authors are not liable for any damages or costs arising from its use.

## 🚀 Features

*   **API Integration:** Connect easily to OpenRouter.ai.
*   **Shortcode Support:** Embed the chat anywhere using a simple shortcode.
*   **Model Selection:** Configure which AI model you want to use via the settings.
*   **Custom Prompts:** Set system instructions to control the AI's behavior.
*   **Minimalist Design:** clean interface that inherits your theme's styling where possible.

## 📋 Requirements

*   WordPress 5.0 or higher
*   PHP 7.4 or higher
*   An API Key from [OpenRouter](https://openrouter.ai/keys)

## 📦 Installation

### Method 1: Zip Upload
1.  Download the plugin repository as a `.zip` file.
2.  Log in to your WordPress Admin Dashboard.
3.  Go to **Plugins > Add New**.
4.  Click **Upload Plugin** at the top.
5.  Select the `.zip` file and click **Install Now**.
6.  Click **Activate**.

### Method 2: Manual Installation
1.  Unzip the archive.
2.  Upload the `openrouter-chat` folder to your server's `wp-content/plugins/` directory using FTP/SFTP.
3.  Go to **Plugins** in your WordPress Dashboard.
4.  Find **OpenRouter Chat** in the list and click **Activate**.

## ⚙️ Configuration

1.  Navigate to **Settings > OpenRouter Chat** in your WordPress dashboard.
2.  **API Key:** Paste your OpenRouter API Key.
3.  **Model:** Enter the model ID you wish to use (e.g., `openai/gpt-4o`, `anthropic/claude-3-opus`, or `meta-llama/llama-3-8b-instruct`).
4.  **System Prompt:** (Optional) Enter instructions for the AI (e.g., *"You are a helpful assistant for a tech support website"*).
5.  Click **Save Changes**.

## 🏃 Usage

To display the chat interface, add the following shortcode to any Page, Post, or Widget:

[openrouter_chat]

## 🤝 Contributing

Contributions are welcome!
1.  Fork the project.
2.  Create your feature branch (`git checkout -b feature/NewFeature`).
3.  Commit your changes (`git commit -m 'Add some NewFeature'`).
4.  Push to the branch (`git push origin feature/NewFeature`).
5.  Open a Pull Request.

## 📄 License

This project is licensed under the MIT License - see the text below for details.

```text
MIT License

Copyright (c) 2025 HelpDesk.ca

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
