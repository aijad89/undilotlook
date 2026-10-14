const { test, expect } = require('@playwright/test');
const { seed, seedAsync } = require('./fixtures/seed');

// Two companies want the last lot at the same moment. This is the one property
// the in-memory SQLite suite cannot test — a single process cannot produce two
// overlapping transactions — so it runs here, against MySQL, as two processes.
//
// A draws and then holds its transaction open for three seconds. B starts one
// second later. LotAllocator reads free plots with `lockForUpdate()`, so B must
// WAIT on A's lock and then find the lot gone. Without the lock, B's read is a
// snapshot that still sees the lot free, and both companies are handed it.

test('two simultaneous confirms for the last lot: exactly one gets it, the other waits and is refused', async () => {
    const { registrations: [a, b] } = JSON.parse(seed('race-setup.php').trim().split('\n').pop());

    const first = seedAsync('race-draw.php', a, 3);
    await new Promise((r) => setTimeout(r, 1000));
    const second = seedAsync('race-draw.php', b, 0);

    const parse = (out) => JSON.parse(out.split('\n').find((l) => l.startsWith('RESULT ')).slice(7));
    const [ra, rb] = (await Promise.all([first, second])).map(parse);

    expect(ra.plot, JSON.stringify(ra)).toBe('LUMBA-RKP1');
    expect(rb.refused, JSON.stringify(rb)).toBe('size_exhausted');

    // B did not answer at once: it waited for A's lock. Had it answered in well
    // under the hold, it would have read past the lock, not through it.
    expect(rb.seconds, JSON.stringify(rb)).toBeGreaterThan(1.2);
});
