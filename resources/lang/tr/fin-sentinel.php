<?php

declare(strict_types=1);

return [

    'navigation' => [
        'settings' => 'Ayarlar',
        'error_channel' => 'Hata Kanal\u0131',
        'error_channel_title' => 'Hata Kanal\u0131 Ayarlar\u0131',
        'debug_channel' => 'Debug Kanal\u0131',
        'debug_channel_title' => 'Debug Kanal\u0131 Ayarlar\u0131',
        'system_logs' => 'Sistem G\u00fcnl\u00fckleri',
        'log_files' => 'G\u00fcnl\u00fck Dosyalar\u0131',
        'log_entries' => 'G\u00fcnl\u00fck Kay\u0131tlar\u0131',
    ],

    'enums' => [
        'navigation_group' => [
            'sentinel' => 'Sentinel',
        ],
        'log_level' => [
            'EMERGENCY' => 'Acil',
            'ALERT' => 'Uyar\u0131',
            'CRITICAL' => 'Kritik',
            'ERROR' => 'Hata',
            'WARNING' => '\u0130kaz',
            'NOTICE' => 'Bildirim',
            'INFO' => 'Bilgi',
            'DEBUG' => 'Debug',
        ],
    ],

    'email' => [
        'header' => [
            'error' => 'Hata Bildirimi',
            'debug' => 'Debug',
            'log_file' => 'G\u00fcnl\u00fck Dosyas\u0131',
        ],
        'footer' => 'Fin-Sentinel taraf\u0131ndan g\u00f6nderildi',

        'label' => [
            'error_message' => 'Hata Mesaj\u0131',
            'class' => 'S\u0131n\u0131f',
            'file' => 'Dosya',
            'context' => 'Ba\u011flam',
            'command' => 'Komut',
            'url' => 'URL',
            'method' => 'Metot',
            'ip' => 'IP',
            'params' => 'Parametreler',
            'headers' => 'Ba\u015fl\u0131klar',
            'name' => 'Ad',
            'email' => 'E-posta',
            'id' => 'ID',
            'user' => 'Kullan\u0131c\u0131',
            'environment' => 'Ortam',
            'debug_mode' => 'Debug Modu',
            'php_version' => 'PHP S\u00fcr\u00fcm\u00fc',
            'laravel_version' => 'Laravel S\u00fcr\u00fcm\u00fc',
            'laravel' => 'Laravel',
            'peak_memory' => 'En Y\u00fcksek Bellek',
            'enabled' => 'Etkin',
            'disabled' => 'Devre D\u0131\u015f\u0131',
            'relation' => '\u0130li\u015fki: :name',
            'bindings' => 'Ba\u011flamalar:',
            'trace_number' => '#',
            'trace_location' => 'Konum',
            'trace_call' => '\u00c7a\u011fr\u0131',
        ],

        'collection' => [
            'count' => ':count \u00f6\u011fe|:count \u00f6\u011fe',
            'more' => '... ve :count \u00f6\u011fe daha',
        ],

        'error' => [
            'subject' => ':app - Bir hata olu\u015ftu',
            'guest' => 'Misafir',
            'console' => 'Konsol',
            'section_exception' => '\u0130stisna Detaylar\u0131',
            'section_trace' => 'Y\u0131\u011f\u0131n \u0130zi',
            'section_request' => '\u0130stek Ba\u011flam\u0131',
            'section_user' => 'Oturum A\u00e7m\u0131\u015f Kullan\u0131c\u0131',
            'section_environment' => 'Ortam',
        ],

        'debug' => [
            'subject' => ':app - Debug: :subject',
            'guest' => 'Misafir',
            'console' => 'Konsol',
            'section_data' => 'Debug Verileri',
            'section_call_site' => '\u00c7a\u011fr\u0131 Noktas\u0131',
            'section_request' => '\u0130stek Ba\u011flam\u0131',
            'section_environment' => 'Ortam',
        ],

        'log_file' => [
            'subject' => ':app - G\u00fcnl\u00fck dosyas\u0131: :file',
            'bulk_subject' => ':app - :count g\u00fcnl\u00fck dosyas\u0131 eklendi',
            'body' => '<strong>:file</strong> g\u00fcnl\u00fck dosyas\u0131 :app uygulamas\u0131ndan ekte g\u00f6nderilmi\u015ftir.',
            'body_text' => ':file g\u00fcnl\u00fck dosyas\u0131 :app uygulamas\u0131ndan ekte g\u00f6nderilmi\u015ftir.',
        ],
    ],

    'settings' => [
        'recipients' => 'Al\u0131c\u0131lar',
        'throttling' => 'H\u0131z S\u0131n\u0131rlama',
        'email_address' => 'E-posta adresi',
        'no_recipients_warning' => 'Al\u0131c\u0131 yap\u0131land\u0131r\u0131lmad\u0131 \u2014 en az bir e-posta adresi eklenene kadar bildirimler g\u00f6nderilmeyecektir.',
        'throttle_rate' => 'H\u0131z s\u0131n\u0131r\u0131',
        'minutes_suffix' => 'dakika',

        'error' => [
            'enabled' => 'Hata bildirimlerini etkinle\u015ftir',
            'enabled_helper' => 'Devre d\u0131\u015f\u0131 b\u0131rak\u0131ld\u0131\u011f\u0131nda hata e-postalar\u0131 g\u00f6nderilmez.',
            'recipients_helper' => 'Hata bildirimlerini alacak e-posta adreslerini ekleyin.',
            'throttle_helper' => 'Ayn\u0131 hata e-postalar\u0131 aras\u0131ndaki minimum dakika.',
            'throttle_exceptions' => '\u0130stisna h\u0131z s\u0131n\u0131rlama',
            'throttle_exceptions_helper' => 'Etkinle\u015ftirildi\u011finde, ayn\u0131 dosya:sat\u0131rdaki tekrarlanan istisnalar h\u0131z s\u0131n\u0131rlama penceresi i\u00e7inde e-posta tetiklemez.',
            'throttle_log_messages' => 'G\u00fcnl\u00fck mesajlar\u0131 h\u0131z s\u0131n\u0131rlama',
            'throttle_log_messages_helper' => 'Etkinle\u015ftirildi\u011finde, ayn\u0131 hata g\u00fcnl\u00fck mesajlar\u0131 h\u0131z s\u0131n\u0131rlama penceresi i\u00e7inde e-posta tetiklemez.',
            'ignored_exceptions' => 'Yoksay\u0131lan \u0130stisnalar',
            'ignored_exceptions_description' => 'Bu listedeki istisnalar e-posta bildirimi tetiklemeyecektir.',
            'ignored_exceptions_label' => 'Yoksay\u0131lan istisnalar',
            'other_custom' => 'Di\u011fer (\u00f6zel)',
            'exception_class' => '\u0130stisna s\u0131n\u0131f\u0131 (FQCN)',
            'class_not_exist' => 'Bu s\u0131n\u0131f mevcut de\u011fil.',
            'custom_exception' => '\u00d6zel istisna',
            'select_exception' => '\u0130stisna se\u00e7in',
        ],

        'debug' => [
            'enabled' => 'Debug kanal\u0131n\u0131 etkinle\u015ftir',
            'enabled_helper' => 'Devre d\u0131\u015f\u0131 b\u0131rak\u0131ld\u0131\u011f\u0131nda Sentinel::debug() \u00e7a\u011fr\u0131lar\u0131 sessizce yoksay\u0131l\u0131r.',
            'recipients_helper' => 'Debug bildirimlerini alacak e-posta adreslerini ekleyin.',
            'throttle_enabled' => 'H\u0131z s\u0131n\u0131rlamay\u0131 etkinle\u015ftir',
            'throttle_enabled_helper' => 'Devre d\u0131\u015f\u0131yken her debug \u00e7a\u011fr\u0131s\u0131 e-posta g\u00f6nderir. Etkinken tekrarlanan \u00e7a\u011fr\u0131lar s\u0131n\u0131rlan\u0131r.',
            'throttle_helper' => 'Ayn\u0131 debug e-postalar\u0131 aras\u0131ndaki minimum dakika.',
        ],

        'test_email' => [
            'send' => 'Test E-postas\u0131 G\u00f6nder',
            'sent' => ':count al\u0131c\u0131ya test e-postas\u0131 g\u00f6nderildi',
            'no_recipients' => 'Al\u0131c\u0131 yap\u0131land\u0131r\u0131lmad\u0131. \u00d6nce en az bir e-posta adresi ekleyin.',
            'failed' => 'Test e-postas\u0131 g\u00f6nderilemedi',
            'channel_disabled' => 'Bu kanal \u015fu anda devre d\u0131\u015f\u0131. Test e-postas\u0131 yine de g\u00f6nderilecektir.',
        ],
    ],

    'logs' => [
        'title' => 'Sistem G\u00fcnl\u00fckleri',
        'heading' => 'G\u00fcnl\u00fck Dosyalar\u0131',
        'entries_title' => 'G\u00fcnl\u00fck Kay\u0131tlar\u0131',
        'back_to_list' => 'G\u00fcnl\u00fck Dosyalar\u0131na D\u00f6n',
        'no_entries' => 'G\u00fcnl\u00fck kayd\u0131 bulunamad\u0131',
        'unsupported_format' => 'Bu dosya standart Laravel g\u00fcnl\u00fck format\u0131n\u0131 kullanm\u0131yor gibi g\u00f6r\u00fcn\u00fcyor',
        'search_placeholder' => 'G\u00fcnl\u00fck kay\u0131tlar\u0131nda ara...',
        'level_filter' => 'G\u00fcnl\u00fck Seviyesi',
        'email_recipient' => 'Al\u0131c\u0131 E-postas\u0131',
        'email_description' => 'Bu g\u00fcnl\u00fck dosyas\u0131n\u0131 belirtilen al\u0131c\u0131ya e-posta eki olarak g\u00f6nderin.',
        'bulk_email_description' => 'Se\u00e7ilen g\u00fcnl\u00fck dosyalar\u0131n\u0131 belirtilen al\u0131c\u0131ya ayr\u0131 e-posta ekleri olarak g\u00f6nderin.',
        'bulk_email_files' => 'Se\u00e7ilen Dosyalar',

        'filter' => [
            'date_from' => 'Ba\u015flang\u0131\u00e7',
            'date_to' => 'Biti\u015f',
        ],

        'column' => [
            'filename' => 'Dosya Ad\u0131',
            'size' => 'Boyut',
            'modified' => 'Son De\u011fi\u015fiklik',
            'subfolder' => 'Alt Klas\u00f6r',
            'level' => 'Seviye',
            'timestamp' => 'Zaman Damgas\u0131',
            'message' => 'Mesaj',
        ],

        'action' => [
            'refresh' => 'Yenile',
            'view' => 'G\u00f6r\u00fcnt\u00fcle',
            'delete' => 'Sil',
            'download' => '\u0130ndir',
            'email' => 'E-posta G\u00f6nder',
            'email_send' => 'G\u00f6nder',
            'email_sent' => 'G\u00fcnl\u00fck dosyas\u0131 ba\u015far\u0131yla e-postaland\u0131',
            'bulk_email_sent' => ':count g\u00fcnl\u00fck dosyas\u0131 ba\u015far\u0131yla e-postaland\u0131',
            'deleted' => 'G\u00fcnl\u00fck dosyas\u0131 silindi',
            'bulk_deleted' => ':count g\u00fcnl\u00fck dosyas\u0131 silindi',
        ],

        'confirm' => [
            'delete' => 'Bu g\u00fcnl\u00fck dosyas\u0131n\u0131 silmek istedi\u011finizden emin misiniz? Bu i\u015flem geri al\u0131namaz.',
            'bulk_delete' => 'Se\u00e7ilen g\u00fcnl\u00fck dosyalar\u0131n\u0131 silmek istedi\u011finizden emin misiniz? Bu i\u015flem geri al\u0131namaz.',
        ],

        'entry' => [
            'detail' => 'Kay\u0131t Detay\u0131',
            'line' => 'Sat\u0131r',
            'trace_frames' => ':count \u00e7er\u00e7eve|:count \u00e7er\u00e7eve',
            'copy_trace' => 'Y\u0131\u011f\u0131n \u0130zini Kopyala',
            'copy_entry' => 'Tam Kayd\u0131 Kopyala',
            'copied' => 'Kopyaland\u0131!',
        ],
    ],

];
