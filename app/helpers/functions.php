<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $action = ''): string
{
    return $action === '' ? '?' : '?action=' . rawurlencode($action);
}
