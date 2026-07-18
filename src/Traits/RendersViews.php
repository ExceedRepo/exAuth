<?php

declare(strict_types=1);

namespace exAuth\Traits;

use exAuth\Config\exAuth as AuthConfig;

trait RendersViews
{
    protected function renderView(string $name, array $data = []): string
    {
        $override = APPPATH . 'Views/exAuth/' . $name . '.php';

        if (file_exists($override)) {
            return view('exAuth/' . $name, $data);
        }

        $authConfig = config(AuthConfig::class);
        $viewKey    = $authConfig->views[$name] ?? $name;

        return view($authConfig->viewPrefix . $viewKey, $data);
    }
}
