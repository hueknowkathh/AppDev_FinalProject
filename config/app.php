<?php

if (!function_exists('loadEnvFile')) {
    function loadEnvFile(string $path): void
    {
        static $loaded = [];

        if (isset($loaded[$path]) || !is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $trimmed, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key === '') {
                continue;
            }

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        $loaded[$path] = true;
    }
}

loadEnvFile(dirname(__DIR__) . '/.env');

if (!function_exists('appEnv')) {
    function appEnv(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('findPythonExecutable')) {
    function findPythonExecutable(): ?string
    {
        $configured = appEnv('PYTHON_PATH');
        if ($configured !== null) {
            return $configured;
        }

        $candidates = [
            'C:\\Users\\My PC\\AppData\\Local\\Programs\\Python\\Python314\\python.exe',
            'C:\\Python314\\python.exe',
            'C:\\Python313\\python.exe',
            'C:\\Python312\\python.exe',
            'py -3',
            'python',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }

            if (str_contains($candidate, ' ') && !str_contains($candidate, '.exe')) {
                return $candidate;
            }

            if ($candidate === 'python') {
                return $candidate;
            }
        }

        return null;
    }
}

if (!function_exists('appOpenAIModel')) {
    function appOpenAIModel(): ?string
    {
        return appEnv('OPENAI_MODEL', 'gpt-5.4-mini');
    }
}
