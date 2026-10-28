# Changelog

All notable changes to this extension are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), versions follow
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [2.2.0] - 2026-10-28

### Added
- TYPO3 14 support alongside 13.4 LTS (`^13.4 || ^14.0`, PHP 8.2–8.4).
- Site set `pixelegg/counter` (`Configuration/Sets/Counter/`) as alternative to the static
  template; settings editable in *Site Management > Settings*.
- TypoScript constants `plugin.tx_pxecounter.view.*` (template/partial/layout root paths, index 10)
  and `plugin.tx_pxecounter.settings.includeAssetsGlobally`.
- Data processor `Pixelegg\PxeCounter\DataProcessing\CounterProcessor`: the final value is
  rendered server-side, formatted for the site language (`NumberFormatter`), and exposed to the
  template as `{counter}`.
- Field descriptions for start/end value, prefix, suffix, grouping and layout; descriptive layout
  names ("Accent", "Inline", "Bold").
- Functional frontend rendering tests for both installation paths and both asset modes;
  unit tests for the locale-aware formatting.
- Public repository scaffolding: GitHub Actions CI matrix (TYPO3 13.4 on PHP 8.2–8.4, TYPO3 14 on
  PHP 8.3–8.4), TER publishing workflow via `typo3/tailor`, `.editorconfig`, `.gitattributes`.

### Changed
- CSS/JS are included only on pages that contain a counter (`<f:asset.css>` / `<f:asset.script>`)
  instead of on every page. Themes that concatenate `page.includeCSS`/`includeJSFooter`
  (e.g. t3sbootstrap) set `plugin.tx_pxecounter.settings.includeAssetsGlobally = 1`.
- The content element renders through `lib.contentElement`: header, frame class and spacing from
  the *Appearance* tab now apply.
- Accessibility: the HTML always contains the final number (no-JS users and screen readers see
  it). The script animates the visible copy, hides it from assistive technology only while
  counting, and restores the original markup afterwards. No `aria-live`.
- Layout stability: the animated value keeps the width of the final number (no shifting while
  counting).
- Number formatting in the browser uses the site language (`data-locale`) instead of the `<html lang>`
  attribute only.
- Backend module moved to *Web > Counter* (no page tree) and rebuilt on the core backend layout
  (table, badges, callout); custom module stylesheet removed.
- User-facing labels without the "PXE" prefix: content element "Counter (animated number)" /
  "Zähler (animierte Zahl)", static template "Counter (animated number)", module "Counter".
- FlexForm registration is version-aware: `addPlugin($item, $flexForm)` on TYPO3 14,
  `addPiFlexFormValue()` on 13.4 (the latter is removed in v15).
- `fluid_styled_content` is declared as dependency (composer and ext_emconf).
- Prefix, suffix and caption inputs are trimmed.
- `CounterUsageService` uses the injectable `FlexFormService`.
- Dev toolchain: PHPStan 2 (level 8, no baseline), phpstan-typo3 2, typo3-rector 3, coding
  standards 0.8/0.9.

### Removed
- Site-wide `page.includeCSS`/`includeJSFooter` default (see *Changed*).
- `Configuration/page.tsconfig`: the new-content wizard entry comes from the TCA item group
  ("Default" tab) on TYPO3 13 and 14; the TSconfig duplicate is gone. Sites that moved the element
  to their own wizard group via TSconfig are unaffected.
- Empty `ext_localconf.php`.
- `CounterData::flatten()` (replaced by `FlexFormService`).
- Custom backend stylesheet `Resources/Public/Css/Backend/module.css`.
- `t3sbootstrap` from the composer keywords: the extension is theme-independent.

### Fixed
- Functional tests loaded the extension under a wrong package name and used PHPUnit annotations
  that PHPUnit 12 no longer recognises.
- Standalone module hiding when pxe_hub is present uses an event listener instead of
  `addUserTSConfig()` (removed in TYPO3 14).

## [2.1.0] - 2026-07-07

### Added
- Backend module listing all counters with health checks (start = end, unknown easing).
- Optional pxe_hub panel (registered only when pxe_hub is installed).

## [2.0.0] - 2026-06-17

### Changed
- Rewritten as a dependency-free vanilla JavaScript content element (no jQuery), rendering via
  `FlexFormProcessor`; `prefers-reduced-motion` respected.

## [1.0.0]

- Initial release (TYPO3 12).

[Unreleased]: https://github.com/pixelegg/pxe-counter/compare/v2.2.0...HEAD
[2.2.0]: https://github.com/pixelegg/pxe-counter/compare/v2.1.0...v2.2.0
[2.1.0]: https://github.com/pixelegg/pxe-counter/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/pixelegg/pxe-counter/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/pixelegg/pxe-counter/releases/tag/v1.0.0
