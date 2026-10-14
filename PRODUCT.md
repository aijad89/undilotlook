# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Kraftangan Malaysia office staff** — super, HQ and state (zone) admins. They work
  **at a desk**, for long stretches: building events, putting companies into a
  draw list with a craft code and booth size, laying out the lot plan, opening and
  closing voting, assigning the rest, exporting results. A state admin sees only
  their own zone's companies.
- **Usahawan (craft entrepreneurs)** — the participants. Mostly **on a phone**. Many
  are **older**, some have **low confidence with apps**. Their job is small and
  high-stakes: see which fair they are in, confirm their lot once, see it revealed,
  keep the slip and check-in code.
- **Visitors** to the public page see published events and are pointed to sign in.

## Product Purpose

undilot runs the **lot draw** for Kraftangan craft fairs: which company gets which
booth. It replaces the lot-draw part of an older system (`daftar`). Success is a
draw everyone trusts: every company seated in a lot of its own craft code and booth
size, decided by one fair rule, with a record of who did what.

## Positioning

The draw is decided by one allocator for every door — the participant's Confirm
and the office's bulk assignment reach the same answer from the same inputs — and a
lot, once confirmed, cannot be drawn twice. The participant never chooses a lot and
never sees how many exist; the reveal is a moment, not a menu.

## Operating Context

- Office flow: event → draw list (company + craft code + booth size) → lot plan
  (locations, sizes, code allocations, or codes painted onto plots) → open voting →
  participants confirm → close → assign remaining → completed. Reports as CSV,
  an audit trail, announcements, a calendar.
- Participant flow: dashboard lists their draws → lot page → tick agreement →
  Confirm (once) → animated reveal (split-flap board or sealed envelope) → lot
  details, check-in QR from their IC, printable/shareable PDF slip.
- Two portals with separate sign-in: admin and participant. A public event list.

## Capabilities and Constraints

- Behaviour is fixed for this experiment (**undilotlook**): **only the look
  changes.** Routes, forms, field names, validation, copy and flows stay as they are.
- Laravel 12 Blade, no build step: styles inline in Blade partials, libraries from a
  CDN with pinned versions. Bootstrap 5.3.3 and Bootstrap Icons 1.11.3 are loaded.
- Two existing design systems: `.mac-` (admin) and `.ap-` (participant). The public
  site has its own `pub-`/`ev-` styles. Auth views have their own look.
- Language is mixed and stays as is in this experiment: participant dashboard and
  public pages in Malay; lot page, slip and admin in English.
- The lot reveal must never imply how many lots exist.

## Brand Commitments

- It must read as an **official Kraftangan Malaysia service** — trustworthy and
  calm, not a startup toy.
- No logo or brand assets are in the repository; none may be invented.
- **Standing direction (undilotlook, 2026-10-14):** the familiar, polished "modern
  app" path, played straight. It sits alongside **GOV.UK / Singpass** (plain,
  readable, accessible, step-by-step government service) and the **Stripe
  Dashboard** (calm, precise admin tables, forms and status). Their craft level is
  the bar.

## Evidence on Hand

- The 2025 craft coding sheet (28 codes, 5 categories) is real reference data.
- No photography, testimonials or statistics exist. Do not fabricate any.

## Product Principles

1. **One step at a time for usahawan.** Every screen says what happens next,
   especially before the one-time Confirm.
2. **The office's screens are for scanning.** Status, counts and the next action
   are readable at a glance across many rows.
3. **Trust over flair.** Nothing may look like a game of chance or hide the rule.
4. **Phone first for participants, desk first for the office.**

## Accessibility & Inclusion

- Older users: generous text size, strong contrast, large touch targets; the
  participant portal's text-size toggle stays.
- Low tech confidence: plain labels, visible state, no gesture-only actions.
- Keyboard reachable throughout (the plot painter's squares are buttons for this).
- Respect `prefers-reduced-motion` (the reveal already does).
