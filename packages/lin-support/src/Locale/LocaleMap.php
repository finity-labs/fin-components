<?php

declare(strict_types=1);

namespace FinityLabs\LinSupport\Locale;

use Illuminate\Support\Str;

/**
 * The locales a Finity Labs package knows by name: each code with its native
 * display name and the flag-icon code that stands for it. One list, shared by
 * every installer that asks which languages to configure and by every
 * settings screen that shows a language row, so "de" is Deutsch with the
 * German flag everywhere.
 *
 * A code outside the list still gets an entry: the intl extension's own
 * display name when it is loaded, the code in upper case otherwise, with the
 * first two letters as the flag.
 */
final class LocaleMap
{
    /**
     * @var array<string, array{display: string, 'flag-icon': string}>
     */
    public const MAP = [
        'am' => ['display' => 'Amharic', 'flag-icon' => 'et'],
        'ar' => ['display' => 'العربية', 'flag-icon' => 'sa'],
        'az' => ['display' => 'Azərbaycanca', 'flag-icon' => 'az'],
        'bg' => ['display' => 'Български', 'flag-icon' => 'bg'],
        'bn' => ['display' => 'বাংলা', 'flag-icon' => 'bd'],
        'bs' => ['display' => 'Bosanski', 'flag-icon' => 'ba'],
        'ca' => ['display' => 'Català', 'flag-icon' => 'es'],
        'ckb' => ['display' => 'کوردی', 'flag-icon' => 'iq'],
        'cs' => ['display' => 'Čeština', 'flag-icon' => 'cz'],
        'cy' => ['display' => 'Cymraeg', 'flag-icon' => 'gb'],
        'da' => ['display' => 'Dansk', 'flag-icon' => 'dk'],
        'de' => ['display' => 'Deutsch', 'flag-icon' => 'de'],
        'el' => ['display' => 'Ελληνικά', 'flag-icon' => 'gr'],
        'en' => ['display' => 'English', 'flag-icon' => 'gb'],
        'es' => ['display' => 'Español', 'flag-icon' => 'es'],
        'eu' => ['display' => 'Euskara', 'flag-icon' => 'es'],
        'fa' => ['display' => 'فارسی', 'flag-icon' => 'ir'],
        'fi' => ['display' => 'Suomi', 'flag-icon' => 'fi'],
        'fr' => ['display' => 'Français', 'flag-icon' => 'fr'],
        'he' => ['display' => 'עברית', 'flag-icon' => 'il'],
        'hi' => ['display' => 'हिन्दी', 'flag-icon' => 'in'],
        'hr' => ['display' => 'Hrvatski', 'flag-icon' => 'hr'],
        'hu' => ['display' => 'Magyar', 'flag-icon' => 'hu'],
        'hy' => ['display' => 'Հայերեն', 'flag-icon' => 'am'],
        'id' => ['display' => 'Bahasa Indonesia', 'flag-icon' => 'id'],
        'it' => ['display' => 'Italiano', 'flag-icon' => 'it'],
        'ja' => ['display' => '日本語', 'flag-icon' => 'jp'],
        'ka' => ['display' => 'ქართული', 'flag-icon' => 'ge'],
        'km' => ['display' => 'ខ្មែរ', 'flag-icon' => 'kh'],
        'ko' => ['display' => '한국어', 'flag-icon' => 'kr'],
        'ku' => ['display' => 'Kurdî', 'flag-icon' => 'iq'],
        'lt' => ['display' => 'Lietuvių', 'flag-icon' => 'lt'],
        'lv' => ['display' => 'Latviešu', 'flag-icon' => 'lv'],
        'mk' => ['display' => 'Македонски', 'flag-icon' => 'mk'],
        'mn' => ['display' => 'Монгол', 'flag-icon' => 'mn'],
        'ms' => ['display' => 'Bahasa Melayu', 'flag-icon' => 'my'],
        'my' => ['display' => 'မြန်မာ', 'flag-icon' => 'mm'],
        'nb' => ['display' => 'Norsk bokmål', 'flag-icon' => 'no'],
        'ne' => ['display' => 'नेपाली', 'flag-icon' => 'np'],
        'nl' => ['display' => 'Nederlands', 'flag-icon' => 'nl'],
        'pl' => ['display' => 'Polski', 'flag-icon' => 'pl'],
        'pt' => ['display' => 'Português', 'flag-icon' => 'pt'],
        'pt_BR' => ['display' => 'Português (Brasil)', 'flag-icon' => 'br'],
        'ro' => ['display' => 'Română', 'flag-icon' => 'ro'],
        'ru' => ['display' => 'Русский', 'flag-icon' => 'ru'],
        'sk' => ['display' => 'Slovenčina', 'flag-icon' => 'sk'],
        'sl' => ['display' => 'Slovenščina', 'flag-icon' => 'si'],
        'sq' => ['display' => 'Shqip', 'flag-icon' => 'al'],
        'sr_Cyrl' => ['display' => 'Српски', 'flag-icon' => 'rs'],
        'sr_Latn' => ['display' => 'Srpski', 'flag-icon' => 'rs'],
        'sv' => ['display' => 'Svenska', 'flag-icon' => 'se'],
        'sw' => ['display' => 'Kiswahili', 'flag-icon' => 'tz'],
        'th' => ['display' => 'ไทย', 'flag-icon' => 'th'],
        'tr' => ['display' => 'Türkçe', 'flag-icon' => 'tr'],
        'uk' => ['display' => 'Українська', 'flag-icon' => 'ua'],
        'ur' => ['display' => 'اردو', 'flag-icon' => 'pk'],
        'uz' => ['display' => 'Oʻzbek', 'flag-icon' => 'uz'],
        'vi' => ['display' => 'Tiếng Việt', 'flag-icon' => 'vn'],
        'zh_CN' => ['display' => '简体中文', 'flag-icon' => 'cn'],
        'zh_TW' => ['display' => '繁體中文', 'flag-icon' => 'tw'],
    ];

    /**
     * Every known code, in list order.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::MAP);
    }

    public static function has(string $code): bool
    {
        return array_key_exists($code, self::MAP);
    }

    /**
     * The settings row for one code: the known entry, or one derived for a
     * code the list does not carry.
     *
     * @return array{code: string, display: string, 'flag-icon': string}
     */
    public static function entry(string $code): array
    {
        $code = trim($code);

        if (self::has($code)) {
            return ['code' => $code, ...self::MAP[$code]];
        }

        $display = extension_loaded('intl') ? (string) \Locale::getDisplayLanguage($code, $code) : '';

        return [
            'code' => $code,
            'display' => $display !== '' && $display !== $code ? Str::ucfirst($display) : strtoupper($code),
            'flag-icon' => strtolower(substr($code, 0, 2)),
        ];
    }

    /**
     * Several codes as settings rows, in the given order, blanks dropped and
     * duplicates folded.
     *
     * @param  iterable<string>  $codes
     *
     * @return list<array{code: string, display: string, 'flag-icon': string}>
     */
    public static function entries(iterable $codes): array
    {
        $rows = [];

        foreach ($codes as $code) {
            $code = trim((string) $code);

            if ($code === '' || isset($rows[$code])) {
                continue;
            }

            $rows[$code] = self::entry($code);
        }

        return array_values($rows);
    }

    /**
     * "Deutsch (de)": what a prompt shows for one code.
     */
    public static function label(string $code): string
    {
        return self::entry($code)['display'].' ('.trim($code).')';
    }
}
