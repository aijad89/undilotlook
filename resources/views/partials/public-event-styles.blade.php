{{-- The public events list and an event's own page share these. --}}
<style>
    .ev-page { max-width: var(--ui-page-max); margin: 0 auto; padding: 56px var(--ui-page-pad) 0; }

    /* Status badge: one word-group a visitor can scan down a column of cards. */
    .ev-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: 0.875rem; font-weight: 600; white-space: nowrap; }
    .ev-badge::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .ev-badge-ok { background: var(--ui-ok-tint); color: var(--ui-ok-text); }
    .ev-badge-info { background: var(--ui-info-tint); color: var(--ui-info-text); }
    .ev-badge-neutral { background: var(--ui-neutral-tint); color: var(--ui-neutral-text); }

    /* Date tile: the day an event starts, readable before the title is. */
    .ev-date { width: 56px; flex-shrink: 0; overflow: hidden; border: 1px solid var(--ui-border); border-radius: var(--ui-r); background: var(--ui-surface); text-align: center; line-height: 1; }
    .ev-date-month { display: block; padding: 5px 0 4px; background: var(--ui-navy-900); color: #fff; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; }
    .ev-date-day { display: block; padding: 7px 0 8px; color: var(--ui-text); font-size: 1.5rem; font-weight: 700; }

    .ev-meta { display: flex; align-items: baseline; gap: 8px; font-size: 1rem; color: var(--ui-muted); }
    .ev-meta i { flex-shrink: 0; }
</style>
