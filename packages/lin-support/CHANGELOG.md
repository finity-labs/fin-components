# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-09-09

First release: the pieces every Finity Labs Laravel package repeated.

### Added

- `Locale\LocaleMap`: sixty locales with their native display name and flag-icon code, `entry()` for one code (derived through the intl extension for a code outside the list), `entries()` for a list of codes as settings rows, and `label()` for prompts. Lifted from fin-mail's installer.
- `Locale\InstalledLocales::detect()`: the locales an application translates, from its lang directory.
- `Console\Concerns\PromptsForLocales`: the "which languages?" step of an install command, answered from a `--locales=` option or an interactive multiselect over the installed locales with a free-text escape for more.
