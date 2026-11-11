# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Kraftangan Malaysia office staff** — three admin roles. **Super** and **HQ**
  work nationally; a **state (zone) admin** sees only their own zone's companies and
  draw rows, though every non-draft event is visible to them so they can fill it.
  They work **at a desk**, for long stretches: building events, putting companies
  into a draw list, laying out the lot plan, opening and closing voting, assigning
  the rest, exporting results. A zone admin checks each join request's payment
  proof and approves it; the company comes in with its own craft code and booth
  size, which only HQ and super can change.
- **Usahawan (craft entrepreneurs)** — the participants. Mostly **on a phone**. Many
  are **older**, some have **low confidence with apps**. Their job is small and
  high-stakes: see which fair they are in, confirm their lot once, see it revealed,
  keep the slip and check-in code. They do not register themselves: the office
  creates their account from the company record.
- **Visitors** see published events on the public site and are pointed to sign in.

## Product Purpose

undilot runs the **lot draw** for Kraftangan craft fairs: which company gets which
booth. Success is a draw everyone trusts: every company seated in a lot of its own
craft code and booth size, decided by one fair rule, with a record of who did what.

## Positioning

The draw is decided by one allocator for every door — the participant's Confirm
and the office's bulk assignment reach the same answer from the same inputs — and a
lot, once confirmed, cannot be drawn twice. The participant never chooses a lot and
never sees how many exist; the reveal is a moment, not a menu. A lot set aside for
a company before the draw stays a surprise — to the company and to its zone admin —
until the company confirms.

## Operating Context

- **Office flow:** event → draw list (company + craft code + booth size; both are
  suggested from the company's own record) → lot plan (locations, each holding
  several booth sizes, with craft-code allocations or codes painted onto plots) →
  open voting → participants confirm → close → assign remaining → completed.
  Opening and closing can run on a schedule. The plan freezes once the first lot is
  held.
- **Admin sidebar, by job:** Dashboard (needs attention, events, companies,
  shortcuts) · User (Companies, HQ Admins, State Admins) · The Event (Events,
  Lot Draw) · Announcements · Setting (Reports as CSV, Dropdown Lists, Craft
  Category Code, Craft Hierarchy, Lot Draw, Footer Pages, Social Links, Audit
  Trail). Everything under Setting except Reports is super/HQ only. HQ manages
  state admins only; HQ Admins is super only.
- **Craft data:** five fixed craft categories, with sub-categories, classifications
  and products beneath them; the office ticks a company's classifications and
  products on its record.
  Craft **codes** (TBPS, RKP…, from the 2025 coding sheet) are a separate list;
  a company carries its usual code, visible to admins only.
- **Participant flow:** dashboard lists their draws → lot page → tick agreement →
  Confirm (once) → animated reveal (split-flap board or sealed envelope, one choice
  for every event under Setting › Lot Draw) → lot details, check-in QR from their
  IC, printable/shareable PDF slip. Notifications (announcements, a reminder before
  voting closes) arrive in the portal.
- **Public site:** an event list and a page per event, sharing the portal's top
  bar and footer; footer pages and social links are edited by the office.
- Two sign-ins, separate: admin and participant.

## Capabilities and Constraints

- Laravel 12 Blade, no build step: styles inline in Blade partials, libraries from a
  CDN with pinned versions. Bootstrap 5.3.3 and Bootstrap Icons 1.11.3 are loaded.
- Two design systems: `.mac-` (admin) and `.ap-` (participant portal). The public
  site adds `ev-` styles. Auth views have their own look.
- **Every screen is in English** — admin, portal, public pages and the PDF slip.
- The app must work under a subpath (e.g. `/undilotnew`), not only at a domain root.
- The lot reveal must never imply how many lots exist.

## Brand Commitments

- It must read as an **official Kraftangan Malaysia service** — trustworthy and
  calm, not a startup toy.
- No logo or brand assets are in the repository; none may be invented.
- **Direction:** the familiar, polished "modern app" path, played straight. It sits
  alongside **GOV.UK / Singpass** (plain, readable, accessible, step-by-step
  government service) and the **Stripe Dashboard** (calm, precise admin tables,
  forms and status). Their craft level is the bar.

## Evidence on Hand

- The 2025 craft coding sheet (28 codes, 5 categories) is real reference data.
- Demo data (14 zone admins, 14 participants with companies, sample events) is
  planted on development machines only, never in production.
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
  text-size toggle is on the portal, public and admin bars.
- Low tech confidence: plain labels, visible state, no gesture-only actions.
- Keyboard reachable throughout (the plot painter's squares are buttons for this).
- Respect `prefers-reduced-motion` (the reveal already does).
