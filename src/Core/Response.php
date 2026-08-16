<?php

namespace App\Core;

class Response
{
    public static function html(string $content, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
    }

    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public static function redirect(string $path): void
    {
        header("Location: $path");
    }

    public static function view(string $template, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . "/../View/$template.php";

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require __DIR__ . '/../View/layout.php';
    }
}
