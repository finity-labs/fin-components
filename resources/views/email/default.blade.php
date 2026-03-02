<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ config('app.name') }}</title>

    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->

    <style>
        /* Reset */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; }

        /* Theme Colors */
        .email-body { background-color: {{ $theme['background'] ?? '#f4f4f7' }}; }
        .email-content { background-color: {{ $theme['content_bg'] ?? '#ffffff' }}; }
        .email-text { color: {{ $theme['text'] ?? '#333333' }}; }
        .email-text-light { color: {{ $theme['text_light'] ?? '#666666' }}; }
        .email-heading { color: {{ $theme['heading'] ?? '#1a1a1a' }}; }
        .email-link { color: {{ $theme['link'] ?? '#4F46E5' }}; }
        .email-button { background-color: {{ $theme['button_bg'] ?? '#4F46E5' }}; color: {{ $theme['button_text'] ?? '#ffffff' }}; }
        .email-footer { background-color: {{ $theme['footer_bg'] ?? '#f4f4f7' }}; color: {{ $theme['footer_text'] ?? '#999999' }}; }
        .email-border { border-color: {{ $theme['border'] ?? '#e8e8e8' }}; }
    </style>
</head>
<body class="email-body" style="margin: 0; padding: 0; background-color: {{ $theme['background'] ?? '#f4f4f7' }};">

    {{-- Preheader (hidden preview text) --}}
    @if(!empty($preheader))
    <div style="display: none; max-height: 0; overflow: hidden;">
        {{ $preheader }}
    </div>
    @endif

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0; padding: 0;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="{{ $branding['content_width'] ?? 600 }}" style="max-width: {{ $branding['content_width'] ?? 600 }}px; width: 100%;">

                    {{-- Logo --}}
                    @if($branding['logo'] ?? null)
                    <tr>
                        <td align="center" style="padding: 20px 0;">
                            <img src="{{ $branding['logo'] }}"
                                 width="{{ $branding['logo_width'] ?? 200 }}"
                                 height="{{ $branding['logo_height'] ?? 50 }}"
                                 alt="{{ config('app.name') }}"
                                 style="display: block;">
                        </td>
                    </tr>
                    @endif

                    {{-- Content --}}
                    <tr>
                        <td class="email-content" style="background-color: {{ $theme['content_bg'] ?? '#ffffff' }}; border-radius: 8px; padding: 40px 30px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 1.6; color: {{ $theme['text'] ?? '#333333' }};">
                            {!! $body !!}
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 20px 30px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; color: {{ $theme['footer_text'] ?? '#999999' }};">
                            @if($branding['footer_links'] ?? [])
                                @foreach($branding['footer_links'] as $link)
                                    <a href="{{ $link['url'] }}" style="color: {{ $theme['link'] ?? '#4F46E5' }}; text-decoration: none;">{{ $link['name'] }}</a>
                                    @if(!$loop->last) &middot; @endif
                                @endforeach
                                <br><br>
                            @endif
                            @if(($branding['customer_service_email'] ?? null) || ($branding['customer_service_phone'] ?? null))
                                @if($branding['customer_service_email'] ?? null)
                                    <a href="mailto:{{ $branding['customer_service_email'] }}" style="color: {{ $theme['link'] ?? '#4F46E5' }}; text-decoration: none;">{{ $branding['customer_service_email'] }}</a>
                                @endif
                                @if(($branding['customer_service_email'] ?? null) && ($branding['customer_service_phone'] ?? null))
                                    &middot;
                                @endif
                                @if($branding['customer_service_phone'] ?? null)
                                    <a href="tel:{{ $branding['customer_service_phone'] }}" style="color: {{ $theme['link'] ?? '#4F46E5' }}; text-decoration: none;">{{ $branding['customer_service_phone'] }}</a>
                                @endif
                                <br><br>
                            @endif
                            {!! __('fin-mail::fin-mail.email.copyright', ['year' => date('Y'), 'app' => config('app.name')]) !!}
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
