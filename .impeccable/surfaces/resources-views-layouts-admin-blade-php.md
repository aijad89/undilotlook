---
version: 1
slug: "resources-views-layouts-admin-blade-php"
primary_target: "resources/views/layouts/admin.blade.php"
related_targets: ["resources/views/layouts/frontend.blade.php","resources/views/layouts/public.blade.php","resources/views/layouts/user-auth.blade.php"]
---

# Surface brief — undilot app shell (admin, participant, public, auth)

Scope: every screen; operate mode. Audience: Kraftangan office staff at a desk; usahawan on a phone, many older, some unsure with apps. Job: run and take part in a lot draw. Constraint: look only — routes, forms, copy and behaviour unchanged. Benchmarks: GOV.UK / Singpass, Stripe Dashboard.

## Direction contract

THESIS: A Kraftangan service that reads like GOV.UK and works like Stripe — one calm, official system from the office desk to the usahawan's phone. It refuses the grey dashboard with one accent, and the frosted Apple imitation it replaces.

OWN-WORLD: Navy shell (rail and app bar), white work surfaces on a cool grey ground, one action blue, Kraftangan gold reserved for "your lot" and the active nav marker, GOV.UK-yellow focus ring. System UI type with tabular figures; hairline borders; 8px rhythm; status always icon + word, never colour alone.

STORY: The office reads each draw's stage and next action at a glance; a usahawan always knows where their draw is and the one step left.

FIRST VIEWPORT: Admin — navy sidebar, page header with title, stage stepper and the primary action at right, then the filter bar and the table. Participant — navy app bar, a large greeting, each draw a card with the stepper and one large button.

FORM: canon (standing exit, user-pinned); seed 244886b2. Signature: the draw-stage stepper on every draw.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
