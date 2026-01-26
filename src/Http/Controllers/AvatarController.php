<?php

declare(strict_types=1);

namespace FinityLabs\FinAvatar\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class AvatarController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $initials = $request->query('initials', '??');
        $bgColor = urldecode($request->query('bg', config('fin-avatar.default_bg') ?? '#1f2937'));
        $textColor = config('fin-avatar.default_text');

        // Secure SVG generation
        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">
            <rect fill="{$bgColor}" x="0" y="0" width="64" height="64"/>
            <text x="50%" y="50%"
                fill="{$textColor}"
                font-family="system-ui, sans-serif"
                font-size="28"
                font-weight="500"
                text-anchor="middle"
                dominant-baseline="central">
                {$initials}
            </text>
        </svg>
        SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Vary' => 'Accept-Encoding',
        ]);
    }
}
