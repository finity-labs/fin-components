# Banner

`banner.html` is the main image for the filamentphp.com plugin listing, built as
plain HTML/CSS so text stays crisp and edits are one-line changes — no image
editor needed.

## Render to PNG (2560×1280)

```bash
npx playwright screenshot --viewport-size=2560,1280 art/banner.html art/banner.png
```

Or open the file in a browser and capture it with DevTools device toolbar at
2560×1280. If the plugin author dashboard asks for a different size, change the
`html, body` dimensions in the file and re-render — the layout is fixed-size on
purpose, so nothing reflows unexpectedly.

## Using a real screenshot instead of the mock

The right-hand card ships with a hand-drawn mock of the component, so the
banner works standalone. To use a real capture instead:

1. Save your screenshot as `art/screenshot.png` (roughly 1200px wide, light or
   dark — dark blends better).
2. In `banner.html`, set `.shot { display: block }` and `.mock { display: none }`.
3. Re-render.

The banner is a source asset — don't commit rendered PNGs; upload them to the
plugin dashboard (and GitHub issue attachments for the README) instead.
