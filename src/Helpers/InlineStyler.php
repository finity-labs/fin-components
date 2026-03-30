<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Helpers;

class InlineStyler
{
    /**
     * Inline theme styles on HTML elements in the email body.
     *
     * Many email clients strip <style> blocks, so CSS classes alone
     * won't work. This method adds inline style attributes to elements
     * that need theme colors applied.
     *
     * @param  array<string, string>  $theme
     */
    public static function apply(string $html, array $theme): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $html = static::inlineLinkStyles($html, $theme);
        $html = static::inlineButtonStyles($html, $theme);

        return $html;
    }

    /**
     * Add inline color to all <a> tags that don't already have inline color set.
     *
     * @param  array<string, string>  $theme
     */
    protected static function inlineLinkStyles(string $html, array $theme): string
    {
        $linkColor = $theme['link'] ?? '#4F46E5';

        return preg_replace_callback(
            '/<a\b([^>]*)>/i',
            function (array $matches) use ($linkColor): string {
                $attributes = $matches[1];

                // Skip links that have the email-button class (styled separately)
                if (preg_match('/class\s*=\s*["\'][^"\']*\bemail-button\b/i', $attributes)) {
                    return $matches[0];
                }

                $linkStyle = "color: {$linkColor}; text-decoration: underline;";

                // If the tag already has an inline style attribute, inject link styles if missing
                if (preg_match('/style\s*=\s*["\']([^"\']*)/i', $attributes, $styleMatch)) {
                    $existing = $styleMatch[1];
                    $additions = '';

                    if (stripos($existing, 'color') === false) {
                        $additions .= "color: {$linkColor}; ";
                    }
                    if (stripos($existing, 'text-decoration') === false) {
                        $additions .= 'text-decoration: underline; ';
                    }

                    if ($additions !== '') {
                        $attributes = preg_replace(
                            '/(style\s*=\s*["\'])/i',
                            '$1'.$additions,
                            $attributes,
                        );
                    }
                } else {
                    $attributes .= ' style="'.$linkStyle.'"';
                }

                return '<a'.$attributes.'>';
            },
            $html,
        ) ?? $html;
    }

    /**
     * Add inline styles to elements with the email-button class.
     *
     * @param  array<string, string>  $theme
     */
    protected static function inlineButtonStyles(string $html, array $theme): string
    {
        $buttonBg = $theme['button_bg'] ?? '#4F46E5';
        $buttonText = $theme['button_text'] ?? '#ffffff';
        $buttonStyle = "background-color: {$buttonBg}; color: {$buttonText}; display: inline-block; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600;";

        return preg_replace_callback(
            '/<a\b([^>]*class\s*=\s*["\'][^"\']*\bemail-button\b[^>]*)>/i',
            function (array $matches) use ($buttonStyle): string {
                $attributes = $matches[1];

                if (preg_match('/style\s*=\s*["\']([^"\']*)/i', $attributes)) {
                    // Replace existing style
                    $attributes = preg_replace(
                        '/style\s*=\s*["\'][^"\']*["\']/i',
                        'style="'.$buttonStyle.'"',
                        $attributes,
                    );
                } else {
                    $attributes .= ' style="'.$buttonStyle.'"';
                }

                return '<a'.$attributes.'>';
            },
            $html,
        ) ?? $html;
    }
}
