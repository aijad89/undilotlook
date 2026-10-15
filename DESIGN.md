---
name: undilot
description: The Kraftangan lot draw, one calm official system from the office desk to the usahawan's phone.
colors:
  navy-950: "#071a33"
  navy-900: "#0b2240"
  navy-800: "#12305a"
  navy-700: "#1b3f70"
  on-navy: "#ffffff"
  on-navy-2: "#b9c6d8"
  on-navy-3: "#d6deea"
  on-navy-soft: "#c3cedd"
  on-navy-icon: "#93a4bb"
  on-navy-dim: "#8fa1ba"
  ground: "#f3f5f8"
  surface: "#ffffff"
  surface-2: "#f8fafc"
  border: "#dce1e8"
  border-strong: "#c3ccd8"
  sep: "#e9edf2"
  border-hover: "#a9b4c3"
  row-hover: "#f6f9ff"
  text: "#101828"
  text-2: "#344054"
  muted: "#586477"
  blue: "#1a56db"
  blue-hover: "#1546b3"
  blue-press: "#103a96"
  blue-tint: "#ebf1fd"
  blue-text: "#1546b3"
  blue-tint-2: "#d7e3fb"
  blue-border: "#b6c9f4"
  blue-border-2: "#9db8ef"
  info-border: "#c4d5f8"
  gold: "#e3a92b"
  gold-tint: "#fdf5e1"
  gold-text: "#7a5300"
  gold-tint-2: "#fbe7bd"
  ok: "#12804a"
  ok-tint: "#e6f4ec"
  ok-text: "#0b6239"
  ok-border: "#b9e1ca"
  warn: "#d98a00"
  warn-tint: "#fdf2df"
  warn-text: "#7f4500"
  warn-border: "#f3d29a"
  warn-border-2: "#efc98a"
  bad: "#c4320a"
  bad-tint: "#fdece8"
  bad-text: "#9a2006"
  bad-border: "#f5c3b6"
  bad-border-2: "#f1b8a8"
  neutral-tint: "#eef1f5"
  neutral-text: "#475467"
  violet-tint: "#f0edfb"
  violet-text: "#5b3fb3"
  shade: "rgba(16, 24, 40, 0.14)"
  scrim: "rgba(7, 26, 51, 0.72)"
  focus: "#ffdd00"
  focus-ink: "#0b0c0c"
typography:
  display:
    fontFamily: "Segoe UI Variable Text, Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "2.25rem"
    fontWeight: 700
    lineHeight: 1.15
    letterSpacing: "-0.02em"
  headline-portal:
    fontFamily: "Segoe UI Variable Text, Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "1.75rem"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "-0.015em"
  stat:
    fontFamily: "Segoe UI Variable Text, Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "1.75rem"
    fontWeight: 700
    lineHeight: 1
    fontFeature: "tnum"
  headline-admin:
    fontFamily: "Segoe UI Variable Text, Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 700
    lineHeight: 1.25
    letterSpacing: "-0.015em"
  figure:
    fontFamily: "Segoe UI Variable Text, Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 700
    letterSpacing: "-0.01em"
    fontFeature: "tnum"
  heading-sm:
    fontFamily: "Segoe UI Variable Text, Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 700
  title-lead:
    fontFamily: "Segoe UI Variable Text, Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 700
  title:
    fontFamily: "Segoe UI Variable Text, Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "1rem"
    fontWeight: 650
    letterSpacing: "-0.005em"
  body-portal:
    fontFamily: "Segoe UI Variable Text, Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.55
  body-admin:
    fontFamily: "Segoe UI Variable Text, Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
  small:
    fontFamily: "Segoe UI Variable Text, Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 600
  label:
    fontFamily: "Segoe UI Variable Text, Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    letterSpacing: "0.02em"
  mono:
    fontFamily: "ui-monospace, Cascadia Mono, Segoe UI Mono, Consolas, monospace"
    fontSize: "0.85em"
rounded:
  xs: "4px"
  sm: "6px"
  md: "8px"
  lg: "12px"
  pill: "999px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "32px"
components:
  button-admin-primary:
    backgroundColor: "{colors.blue}"
    textColor: "{colors.on-navy}"
    rounded: "{rounded.md}"
    padding: "0 16px"
    height: "40px"
  button-admin-primary-hover:
    backgroundColor: "{colors.blue-hover}"
  button-admin-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.md}"
    padding: "0 16px"
    height: "40px"
  button-portal-primary:
    backgroundColor: "{colors.blue}"
    textColor: "{colors.on-navy}"
    rounded: "{rounded.md}"
    padding: "0 20px"
    height: "48px"
  button-portal-primary-hover:
    backgroundColor: "{colors.blue-hover}"
  your-lot:
    backgroundColor: "{colors.gold-tint}"
    textColor: "{colors.gold-text}"
    rounded: "{rounded.md}"
    height: "48px"
  input-admin:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.md}"
    padding: "0 12px"
    height: "40px"
  input-portal:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.md}"
    padding: "10px 14px"
    height: "48px"
  card:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.lg}"
    padding: "24px"
  status-ok:
    backgroundColor: "{colors.ok-tint}"
    textColor: "{colors.ok-text}"
    rounded: "{rounded.pill}"
    padding: "2px 10px 2px 8px"
  status-warn:
    backgroundColor: "{colors.warn-tint}"
    textColor: "{colors.warn-text}"
    rounded: "{rounded.pill}"
    padding: "2px 10px 2px 8px"
  status-bad:
    backgroundColor: "{colors.bad-tint}"
    textColor: "{colors.bad-text}"
    rounded: "{rounded.pill}"
    padding: "2px 10px 2px 8px"
  nav-rail:
    backgroundColor: "{colors.navy-900}"
    textColor: "{colors.on-navy}"
    width: "264px"
  nav-rail-item-active:
    backgroundColor: "{colors.navy-700}"
    textColor: "{colors.on-navy}"
    rounded: "{rounded.md}"
  app-bar:
    backgroundColor: "{colors.navy-900}"
    textColor: "{colors.on-navy}"
    height: "64px"
---

# Design System: undilot

## Overview

**Creative North Star: "The Service Counter"**

undilot reads like a government service and works like a well-made dashboard: a navy shell that holds still, white work surfaces on a cool grey ground, one blue that acts. The office sits at a desk for hours scanning draw lists, so its screens are dense, Stripe-calm tables and forms at 40px controls. Usahawan, many older and on a phone, get the same world at a larger size: one reading column (760px), 1rem body, 48px targets, one large button per draw.

Nothing is decorative. Depth is a hairline and a whisper of shadow; colour carries meaning (blue acts, gold marks what is yours, state colours always name themselves in words). The one authored moment is the draw-stage stepper's current stop settling in. The system refuses the grey dashboard with a single accent and the frosted Apple imitation it replaced.

The whole thing ships with **no build step**: every stylesheet is an inline `<style>` in a Blade partial, and the only libraries (Bootstrap 5.3.3, Bootstrap Icons 1.11.3) come from a CDN with pinned versions and SRI hashes. Tokens live once, as `--ui-*` custom properties in `partials/ui-tokens.blade.php`; nothing else spells a hex value except the two places CSS variables cannot reach (see The No Stray Hex Rule).

**Key Characteristics:**
- Navy shell (rail, app bar, sign-in panel), white surfaces on a cool grey ground.
- One action blue; Kraftangan gold only for "yours", "you are here" and the mark.
- GOV.UK yellow-and-black focus ring on every focusable thing.
- System UI type with tabular figures on every number.
- Hairline borders, 12px card corners, 8px control corners.
- The draw-stage stepper on every draw, office and portal alike.
- Two component prefixes that never meet: `.mac-` for the office, `.ap-` for usahawan.

## Colors

A cool, official palette: navy for the frame, white for work, one saturated blue for action, and a single warm gold held back for ownership.

### Primary
- **Action Blue** (`blue`): every primary button, filter button, checked checkbox, active tab underline, the stepper's done and current stops, link text (`blue-text`). Hover deepens to `blue-hover`, press to `blue-press`; `blue-tint` is the quiet background for info alerts, active text-size toggles, row-action hover and an unread notification. `blue-tint-2` is the deeper tint; `blue-border` (row-action hover, unread notification), `blue-border-2` (portal outline button) and `info-border` (info alert) are its hairlines; `row-hover` is the near-white blue wash on an admin table row.

### Secondary
- **Kraftangan Gold** (`gold`, `gold-tint`, `gold-text`): the usahawan's own lot (the lot link on a draw card, the lot code on the lot page, the reveal), the active-nav marker (a 3px bar in the admin rail, an inset bar in the portal drawer, an underline in the portal and public app bars, the active nav icon), and the brand-mark tile on every shell and sign-in panel. Text on gold is always `gold-text` on `gold-tint`, never white on gold. `gold-tint-2` is the lot link's hover.

### Tertiary
- **Service Navy** (`navy-950` to `navy-700`): the shell. `navy-900` is the admin rail, portal and public app bar, the sign-in panel and the active pagination page; `navy-700` is the active nav item fill; `navy-950` is ink on gold. Text on navy is `on-navy`, secondary `on-navy-2`; the nav uses four more greys on navy: `on-navy-3` for item text, `on-navy-soft` for submenu items, `on-navy-icon` for resting icons, `on-navy-dim` for section labels and chevrons. Pure white on navy stays the literal `#fff`.

### Neutral
- **Cool Ground** (`ground`): page background under every shell.
- **White Surface** (`surface`) and **Wash** (`surface-2`): cards, inputs, bars; `surface-2` for table headers, card footers and input-group addons.
- **Hairlines** (`border`, `border-strong`, `sep`, `border-hover`): `border` outlines cards and bars, `border-strong` outlines controls, `sep` divides rows inside a card, `border-hover` is a control's border under the pointer.
- **Shade** (`shade`): a translucent ink edge that darkens the border of a coloured fill, such as a painted plot tile.
- **Scrim** (`scrim`): `navy-950` at 72%, behind a modal. The mobile nav drawers keep their lighter navy scrims (see Layout).
- **Ink** (`text`, `text-2`, `muted`): headings and values, labels and secondary copy, metadata and placeholders.
- **State pairs** (`ok`, `warn`, `bad`, `neutral`, `violet`, each with `-tint` and `-text`): text on tint, every pair at least 4.5:1. The solid `ok`/`warn`/`bad` are for fills (success button, progress bar, danger hover, badge counts); words use the `-text` shade. Tinted surfaces take a matching hairline: `ok-border`, `warn-border` and `bad-border` for alerts, the stronger `warn-border-2` and `bad-border-2` for warning and danger buttons and the danger row action.

### Named Rules
**The One Blue Rule.** Only `blue` acts. A secondary action is white with a strong hairline; a destructive action is white with `bad-text` until hover turns it solid `bad`.

**The Gold Means Yours Rule.** Gold marks the usahawan's lot, where you are in the nav, and the mark. It is never a button fill, a status, or a highlight for anything else.

**The No Stray Hex Rule.** Colour comes from `--ui-*` tokens, and no view, partial or script outside `ui-tokens` spells a hex value. There are exactly two exceptions, both because CSS variables cannot reach them, and both name the token each value mirrors: `App\Support\CalendarPalette::PALETTE` (PHP data written into inline styles and event data, one comment per value) and `pdf/lot-result.blade.php` (the PDF renderer reads no CSS variables; a header comment lists the tokens). Change a token, change it there too. The local-only `dev/auto-login` view is out of scope. The legacy `--mac-*`, `--ap-*`, `--adm-*`, `--apple-*` and `--pub-*` names survive only as aliases onto `--ui-*` so old inline styles resolve; new code uses `--ui-*` directly.

## Typography

**Body Font:** Segoe UI Variable Text (with Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Arial, sans-serif)
**Mono Font:** ui-monospace (with Cascadia Mono, Segoe UI Mono, Consolas), for `code` only

**Character:** One system sans at every level, set firm (600 to 700 for headings and labels) with slight negative tracking on headings. It reads as a service, not a brand.

### Hierarchy
Nine sizes and no others: 0.75, 0.8125, 0.875, 1, 1.125, 1.25, 1.5, 1.75 and 2.25rem. Every rem size in the views (outside `pdf/` and `dev/`) is one of them; a new size snaps to the nearest step. The sign-in panel title's `clamp(2rem, 3.4vw, 2.8rem)` is the one fluid size.

- **Display** (700, 2.25rem, -0.02em): the public event list's headline (1.5rem on a phone); also the size of large state glyphs (empty-state and done icons).
- **Headline, portal** (700, 1.75rem, 1.2; 1.5rem under 576px): the page title and greeting in the participant portal.
- **Stat** (700, 1.75rem, 1, tabular): portal stat values and the plan reference's counts.
- **Headline, admin** (700, 1.5rem, 1.25; 1.25rem under 768px): the office page title; also the sign-in form heading and the portal identity name.
- **Figure** (700, 1.5rem, tabular): the counts in a draw's band, a lot fact's value, the lot code in the reveal.
- **Small heading** (700, 1.25rem): a draw card's title.
- **Lead / section title** (700, 1.125rem): portal card, section and empty-state titles, public event titles and section heads, brand names, the sign-in subtitle, and the lot code chip on the lot page.
- **Title** (650, 1rem): admin card titles, the admin empty-state title, a notification's title.
- **Body** (400; 1rem/1.55 in the portal, 0.875rem in admin tables, fields, alerts and details): portal reading copy, labels and table cells are 1rem; portal secondary copy (breadcrumb, small button, timeline note, footer, stepper labels) is 0.875rem and nothing in the portal goes smaller except badges and table headers. Footer panel copy caps at 72ch.
- **Small** (600 or 500, 0.8125rem): admin meta and secondary copy (breadcrumb, status pill, row action, form label, detail label, pagination, section title, `.mac-muted`), stacked-table cell labels, portal table headers and fact labels.
- **Label** (600, 0.75rem, 0.02em): admin table headers, nav section labels, `.mac-badge`, nav count badges and stepper labels, sentence case.

### Named Rules
**The Tabular Figures Rule.** Every table cell, `time`, draw meta line, lot code and count sets `font-variant-numeric: tabular-nums`, so columns of numbers line up when scanned.

**The Bigger For Usahawan Rule.** The portal sets every equivalent role one step larger than admin, and its text-size toggle sets the root to 1.125rem / 1.25rem (112.5% / 125%). The office gets the same toggle at 112% / 124%.

## Layout

Two spatial models over one token set.

- **Office (admin layout):** a fixed 264px navy rail on the left, a sticky 60px white top bar, content on the grey ground capped at 1360px. Pages run header (breadcrumb above the title, primary action at right), then a filter bar, then a table card. Below 768px the rail becomes a drawer over a navy scrim (`navy-950` at 45%) and the bar drops to 56px.
- **Portal (frontend layout):** a sticky 64px navy app bar and one reading column of 760px, padded 32px 24px (20px 16px on a phone). At 992px and up the nav sits in the app bar; below, it moves to a 280px drawer and the bar keeps only menu, title, text size, notifications and the user.
- **Public layout:** the same navy bar as the portal, a 1120px column, a white footer pinned to the window bottom.

Rhythm is 8px: control gaps 8px, card padding 20 to 24px, page sections 16 to 24px apart, portal section heads 36px above. Breakpoints: 576px (portal compact), 768px (admin stacks), 992px (portal nav moves into the bar).

### Named Rules
**The Tables Become Cards Rule.** Below 768px every `.mac-table` stacks: each row a bordered 12px card, each cell a label/value line (the value wrapped in `.mac-cell`) whose label is copied from the column header by script. A cell holding one field is labelled like any value; a cell with a button or more than one control, a blank header or a `colspan` is skipped. A wide one-form row (`td[colspan]`) instead shows each field's `aria-label` above it, label and field sharing a `.mac-field` box (the label `aria-hidden`; the box is `display: contents` above 768px), with the row's button on the fields' baseline; a worded button there takes its own full-width line under the fields. Detail rows stack label over value, full width. The outer card gives up its chrome so cards never nest in cards, unless it carries `.mac-card-keep`: a small table inside a detail card keeps the card, and its rows become `sep`-divided lines inside it, labelled at the detail-label size (0.8125rem/500).

**The Say What It Does Rule.** Below 768px an icon-only row button shows its own `title` as a visible word beside the icon (`.mac-btn-word`, added by script, `aria-hidden` so the accessible name still comes from the title), and grows to 40px. Older users should not have to guess what a bare tick or bin does. Above 768px the word is hidden.

## Elevation & Depth

Flat by default, separated by hairlines; shadow is a whisper at rest and only real when something floats.

### Shadow Vocabulary
- **Rest** (`--ui-shadow-1`): cards, filter bar, buttons. Barely there; the border does the work.
- **Raised** (`--ui-shadow-2`): reserved; defined but not yet used.
- **Pop** (`--ui-shadow-pop`): dropdown menus and the open mobile drawer, the only things that sit above the page.
- **Button bevel**: primary buttons add an inset 1 to 2px dark bottom edge over Rest.

### Named Rules
**The Hairline First Rule.** A surface is separated from the ground by a 1px `border`, not by shadow. Never stack a shadow heavier than Rest on anything that does not float.

## Shapes

Gently rounded and consistent, on five steps: 12px (`lg`) for every card, filter bar, alert, dropdown and stacked-table row; 8px (`md`) for every button, input, nav item and icon tile; 6px (`sm`) for row actions, badges, dropdown items, pagination and calendar events; 4px (`xs`) for small things: inline `code` chips, colour swatches and calendar dots, small thumbnails, the stepper's track and pips. Status pills, avatars, nav badges and the progress bar are fully round. No other radius: the one exception is the 3px-wide gold active-nav marker, whose 3px corners are sized to its width. The brand mark is a gold square with 8px corners in the shells and 12px on the sign-in panel, holding a grid icon. Borders are always 1px, except the 2px stepper ring and the portal's 2px confirm checkbox.

## Components

### Buttons
- **Shape:** gently rounded (8px).
- **Admin:** 40px tall, 0 16px, 0.875rem/600. Primary is solid blue with a bevel; secondary white with `border-strong`; danger and warning are white with a tinted border and state text, filling solid on hover; success is solid `ok`.
- **Portal:** 48px tall, 0 20px, 1rem/600; `-sm` 40px, `-lg` 56px. Adds an outline variant (white, blue text, light-blue border). On a phone the draw card's button is full width.
- **Row actions (admin):** 32px, 6px corners, 0.8125rem/600, white with `border-strong`; hover tints blue. On a phone an icon-only one shows its title as a word and is 40px tall (see The Say What It Does Rule).
- **Hover / Focus:** colour transitions at 140ms on the one ease-out curve; focus is the yellow ring. Disabled drops to 45% opacity with no shadow.

### Status
- **Style:** a fully round pill, tint background, `-text` word, 0.8125rem/600 in admin (`.mac-status`), 0.875rem in the portal (`.ap-badge`), led by a 7px `currentColor` marker.
- **Rule:** the word is always present; colour only reinforces it. Square-cornered `.mac-badge` is for counts and tags, not state.

### Cards / Containers
- **Corner Style:** 12px.
- **Background:** white on the grey ground.
- **Shadow Strategy:** Rest (see Elevation).
- **Border:** 1px `border`; headers and rows divided by `sep`.
- **Internal Padding:** admin 24px (header 14px 20px); portal 20px 22px (header 16px 22px, footer on `surface-2`).

### Inputs / Fields
- **Style:** white, 1px `border-strong`, 8px corners; 40px tall in admin, 48px in the portal and sign-in. Selects carry an inline SVG chevron. Hover darkens the border.
- **Focus:** mouse focus shows a blue border with an 18% blue halo; keyboard focus shows the yellow ring with an ink border.
- **Error:** border `bad`, message in `bad-text`.
- **Labels:** 0.8125rem/600 `text-2` in admin, 1rem/600 in the portal and sign-in.
- **One-line text that may run long** (a craft code's breakdown): a one-row textarea that grows with its text (`field-sizing: content`, a script fallback where unsupported), 40px at rest, no resize handle. Enter submits the row, as the input it replaced did. On a phone the row's save tick sits beside it on the same line.

### Navigation
- **Admin rail:** navy, 264px, gold brand tile at top, sentence-case section labels in muted blue-grey. Items 40px, `on-navy` at 85%, icons muted; hover a 7% white wash; active fills `navy-700`, turns the icon gold and draws a 3px gold bar on the left edge. Submenu active is a white wash with no bar.
- **Portal and public bars:** navy, white brand; links 40px, active marked by a 3px gold underline. Portal drawer items are 52px tall at 1rem, active with an inset gold bar.

### Draw-Stage Stepper (signature)
Four stops (draft, open, closed, done) on every draw. Passed stops are solid blue with a check; the current stop is a white dot ringed in blue with a blue centre and a 4px `blue-tint` halo that settles in once (420ms, skipped under reduced motion); the line is drawn blue up to the current stop. A cancelled draw greys the whole track. The compact form (`.ui-stepper-compact`) is four 18x6px pips plus the current stage's word, for a table cell. The portal sets its labels larger (0.875rem). It is `aria-current="step"` on the current stop.

### Draw Band and Draw Card
The admin draw page opens with a band: the stepper at left and the counts (Figure type) at right behind a `sep` rule. The portal dashboard shows each draw as a card: title, meta line with icons, status, stepper, then one row with the next step, which is the gold "your lot" link once a lot is held.

### Page Header (admin)
Breadcrumb, title, then any lede, with the primary action at right. The breadcrumb always reads above the title: where a view writes it after the `h2`, the stylesheet reorders it. Crumbs are underlined `blue-text` links in 0.8125rem `muted`, separated by one chevron (`<span class="sep" aria-hidden="true">›</span>`) in `border-strong`, never a slash.

### Alerts (admin)
Block layout, not flex, so a `<strong>` and the plain text after it run as one sentence: 12px corners, 0.875rem/1.5, the state tint with its `-border` hairline and `-text` words. A leading icon hangs in a 40px left padding; an alert without one pads 14px.

### Inline Code
A neutral chip: `neutral-tint` ground, `text-2` ink, mono at 0.85em, 1px 5px padding, 4px corners.

### Empty State (admin)
Centred in its card at 48px 24px: a 52px `neutral-tint` icon tile with 12px corners and a `muted` icon, a title (1rem/650, `text`), then the reason in at most 56ch.

### Pagination (admin)
The page nav is the card's padded footer (12px 16px) under a `sep` hairline, with the "Showing x to y of z" line in 0.8125rem `muted`. Page links are 34px with 6px corners; the active page is solid `navy-900`. On a phone, Previous and Next are 44px white buttons with `border-strong`, pushed to either end.

### Calendar (admin)
FullCalendar themed through its own CSS variables onto the tokens: `sep` grid lines, `blue-tint` today with a `blue-text` day number. Toolbar buttons are 36px white with `border-strong`; the active view is solid `navy-900`. Event colours come from `CalendarPalette`: draw state in its state colours, registration windows neutral with ok/bad caps, voting windows in action blue (voting is the thing to act on). The grid grows to its rows (auto height); on a phone every bar shows, with no "+N more", and the toolbar wraps title over arrows over views.

### Plot Painter (admin)
Each kraf code gets a categorical hue, spread evenly round the wheel from blue at 55% saturation and 30% lightness, so the white code on every tile clears 4.5:1 (measured 5.36 to 10:1). An unpainted tile is plain surface. A locked tile is hatched with a 135-degree stripe, never faded, so its code stays legible.

### Notifications (portal)
An unread notification is its card on `blue-tint` with a `blue-border` hairline; read ones are plain white. Title 1rem/650, message 1rem `text-2`.

### Sign-in
The auth layouts carry their own look and never use `.mac-` or `.ap-` classes: a navy panel (optionally a photo under a 72% navy overlay) with the gold mark and title, beside a 480px white form panel that restyles Bootstrap's own form classes to 48px inputs and a 52px blue button. Below 860px the panel stacks above the form.

## Do's and Don'ts

### Do:
- **Do** take every colour, radius, shadow and duration from `--ui-*` in `partials/ui-tokens.blade.php`.
- **Do** write styles inline in a Blade partial and load libraries from a pinned CDN URL; there is no build step and no Node in production.
- **Do** use `.mac-` classes on office pages and `.ap-` classes on participant pages, and only one of the two on any page.
- **Do** give every focusable element the GOV.UK ring (`0 0 0 3px #ffdd00, 0 0 0 5px #0b0c0c`); plain text links take the yellow highlighter with a black underline instead.
- **Do** show status as a word with its marker, never as colour alone.
- **Do** put the draw-stage stepper on every draw, full on a draw's own page and compact in a table row.
- **Do** keep portal targets at 48px, body text at 1rem, and secondary text (breadcrumb, small buttons, footer, stepper labels) no smaller than 0.875rem.

### Don't:
- **Don't** use gold for anything except the usahawan's lot, the active-nav marker and the mark.
- **Don't** introduce a second action colour; blue is the only one that acts.
- **Don't** put `.mac-` or `.ap-` classes on the sign-in views; they carry their own look.
- **Don't** spell a hex value outside `ui-tokens`; the only exceptions are `CalendarPalette::PALETTE` and the PDF slip, each naming the token it mirrors.
- **Don't** add a shadow heavier than Rest to anything that does not float.
- **Don't** mark an unread or active row with a coloured side stripe; use the blue tint and its hairline.
- **Don't** fade a locked tile whose label must stay readable; hatch it.
- **Don't** suppress the focus ring or replace it with a blue glow for keyboard users.
