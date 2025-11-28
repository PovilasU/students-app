<?php

class View
{
    public function render(string $template, array $params = []): void
    {
        extract($params, EXTR_SKIP);

        // __DIR__ = /src
        // ../views = projekto_root/views
        require __DIR__ . '/../views/' . $template;
    }
}
