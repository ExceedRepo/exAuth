<?php

declare(strict_types=1);

namespace exAuth\Commands\Setup;

class ContentReplacer
{
    public function replace(string $content, array $replaces): string
    {
        return strtr($content, $replaces);
    }

    /**
     * @return bool|string true: already updated, false: regexp error
     */
    public function add(string $content, string $text, string $pattern, string $replace)
    {
        $return = preg_match('/' . preg_quote($text, '/') . '/u', $content);

        if ($return === 1) {
            return true;
        }

        if ($return === false) {
            return false;
        }

        return preg_replace($pattern, $replace, $content);
    }
}
