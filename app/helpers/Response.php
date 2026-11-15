<?php

declare(strict_types=1);

class Response
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, max-age=0');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function ok(array $data = []): never
    {
        self::json(array_merge(['success' => true], $data));
    }

    public static function error(string $message, int $status = 400, array $extra = []): never
    {
        self::json(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), $status);
    }

    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}
