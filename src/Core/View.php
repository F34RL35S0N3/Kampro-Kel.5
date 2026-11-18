<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layout'): void
    {
        $templatePath = BASE_PATH . '/templates/' . $template . '.php';
        if (!is_file($templatePath)) {
            throw new \RuntimeException('Template tidak ditemukan: ' . $template);
        }

        $data['config'] = $data['config'] ?? (array) config();

        extract($data, EXTR_SKIP);

        ob_start();
        require $templatePath;
        $content = (string) ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutPath = BASE_PATH . '/templates/' . $layout . '.php';
        if (!is_file($layoutPath)) {
            echo $content;
            return;
        }
        require $layoutPath;
    }
}
