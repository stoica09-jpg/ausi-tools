# ausi-tools

Joomla 4 / 5 site modules.

| Module | Folder | What it does |
| --- | --- | --- |
| **Adrian Cookie** | [`mod_adrian_cookie`](mod_adrian_cookie) | EU cookie consent banner (Accept All / Essential Only) |
| **Adrian Accessibility Tools** | [`mod_adrian_accessibility_tools`](mod_adrian_accessibility_tools) | Floating WCAG accessibility widget |

Both modules ship English (`en-GB`) and Romanian (`ro-RO`) language files and load their CSS/JS
through the Joomla WebAssetManager (`media/<module>/joomla.asset.json`).

## Build the install packages

```bash
./build.sh
```

This creates `dist/mod_adrian_cookie.zip` and `dist/mod_adrian_accessibility_tools.zip`.

To zip by hand instead: open the module folder, select **its contents** (the `.xml` manifest,
the `.php` file and the `tmpl`, `media`, `language` folders) and compress them. The `.xml`
manifest must be at the root of the zip, not inside a sub-folder.

## Install

1. Joomla administrator → **System → Install → Extensions** → **Upload Package File**.
2. Drop the `.zip`.
3. **Content → Site Modules → New** → pick *Adrian Cookie* or *Adrian Accessibility Tools*.
4. Choose any template position (e.g. `debug` or `footer` — both modules are `position: fixed`,
   so the position only decides that it gets rendered), set **Status: Published**,
   and under **Menu Assignment** choose **On all pages**.
5. Configure the options and **Save**.

## Adrian Cookie

The choice is stored in the `adrian_cookie_consent` cookie (`all` or `essential`) for 365 days.
The banner only slides in when that cookie is missing.

"Essential Only" does not block anything by itself — load your analytics / marketing scripts only
when the visitor accepted them:

```js
// On page load
if (window.AdrianCookie && AdrianCookie.get() === 'all') { /* load analytics */ }

// Right after the visitor clicks a button
document.addEventListener('adrian-cookie-consent', function (e) {
	if (e.detail.consent === 'all') { /* load analytics */ }
});
```

`AdrianCookie.reset()` deletes the cookie and shows the banner again (useful for a
"Cookie settings" link: `<a href="#" onclick="AdrianCookie.reset();return false;">Cookie settings</a>`).
PHP can read the same choice with `$app->getInput()->cookie->get('adrian_cookie_consent')`.

## Adrian Accessibility Tools

Floating button (inline SVG of the universal accessibility symbol, no icon fonts) that opens a
slide-out panel. Every tool can be switched on/off from the module options:

| Tool | Class on `<html>` |
| --- | --- |
| Invert colours | `adrian-invert` |
| Monochrome | `adrian-monochrome` |
| Dark contrast | `adrian-dark-contrast` |
| Light contrast | `adrian-light-contrast` |
| Highlight links | `adrian-highlight-links` |
| Highlight headings | `adrian-highlight-headings` |
| Readable font | `adrian-readable-font` |
| Text size (80 % – 150 %) | scaled by JavaScript |
| Reset all | — |

Preferences are saved in `localStorage` (`adrian_a11y_prefs`) and restored before the page paints.
The panel is keyboard accessible: buttons expose `aria-pressed`, `Esc` closes the panel and returns
focus to the floating button, and changes are announced to screen readers.
