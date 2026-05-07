<?php

namespace App\Enums;

enum SnippetLanguage: string
{
    case PHP = 'php';
    case JAVASCRIPT = 'javascript';
    case TYPESCRIPT = 'typescript';
    case PYTHON = 'python';
    case SQL = 'sql';
    case HTML = 'html';
    case CSS = 'css';
    case SHELL = 'shell';
    case DOCKERFILE = 'dockerfile';
    case YAML = 'yaml';
    case JSON = 'json';
    case MARKDOWN = 'markdown';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match($this) {
            self::PHP => 'PHP',
            self::JAVASCRIPT => 'JavaScript',
            self::TYPESCRIPT => 'TypeScript',
            self::PYTHON => 'Python',
            self::SQL => 'SQL',
            self::HTML => 'HTML',
            self::CSS => 'CSS',
            self::SHELL => 'Shell/Bash',
            self::DOCKERFILE => 'Dockerfile',
            self::YAML => 'YAML',
            self::JSON => 'JSON',
            self::MARKDOWN => 'Markdown',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PHP => '🐘',
            self::JAVASCRIPT => '📜',
            self::TYPESCRIPT => '📘',
            self::PYTHON => '🐍',
            self::SQL => '🗄️',
            self::HTML => '🌐',
            self::CSS => '🎨',
            self::SHELL => '💻',
            self::DOCKERFILE => '🐳',
            self::YAML => '⚙️',
            self::JSON => '📋',
            self::MARKDOWN => '📝',
            self::UNKNOWN => '❓',
        };
    }

    public function prismComponent(): string
    {
        return match($this) {
            self::PHP => 'language-php',
            self::JAVASCRIPT => 'language-javascript',
            self::TYPESCRIPT => 'language-typescript',
            self::PYTHON => 'language-python',
            self::SQL => 'language-sql',
            self::HTML => 'language-html',
            self::CSS => 'language-css',
            self::SHELL => 'language-bash',
            self::DOCKERFILE => 'language-docker',
            self::YAML => 'language-yaml',
            self::JSON => 'language-json',
            self::MARKDOWN => 'language-markdown',
            self::UNKNOWN => 'language-none',
        };
    }
}
