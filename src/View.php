<?php

class View
{
    public function render(string $template, array $params = []): void
    {
        extract($params, EXTR_SKIP);
        require __DIR__ . '/../views/' . $template;
    }
}
