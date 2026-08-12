# Art assets

## Main image (`main-image.html` → `main-image.jpeg`)

The filamentphp.com plugin main image: flat icon-only design per the submission
requirements — modal-window-with-selected-row motif, amber #EBB304 on #111827,
no text, 16:9 at 2560×1440, JPEG. The rendered `main-image.jpeg` is committed
so it's ready to upload; re-render after edits with:

```bash
npx playwright screenshot --viewport-size=2560,1440 "file://$PWD/art/main-image.html" art/main-image.jpeg
```

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
