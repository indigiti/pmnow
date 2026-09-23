<?php
namespace PuneMirror\Core;

final class View
{
    public function __construct(private readonly string $root) {}

    public function render(string $view, array $data = []): string
    {
        $viewFile = $this->root . '/resources/views/' . $view . '.php';
        if (!is_file($viewFile)) throw new \RuntimeException("View missing: $view");
        extract($data, EXTR_SKIP);
        $viewRoot = $this->root . '/resources/views';
        ob_start();
        require $viewFile;
        return (string)ob_get_clean();
    }
}
