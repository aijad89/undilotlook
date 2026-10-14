const { spawn, spawnSync } = require('child_process');
const path = require('path');

const ROOT = path.resolve(__dirname, '..', '..', '..');
const PHP = process.env.PHP_BINARY || 'php';

/**
 * The one place a spec shells out to PHP. Every call goes through run.php,
 * which fails loudly on a throw and refuses any database but the e2e copy.
 *
 * `env` is passed explicitly although it is the default: the variables that have
 * to travel are named here, in the file the specs read.
 */
function seed(script, ...args) {
    const result = spawnSync(PHP, ['tests/e2e/fixtures/run.php', script, ...args], {
        cwd: ROOT, env: process.env, encoding: 'utf8', shell: true,
    });

    if (result.status !== 0) {
        throw new Error(`fixture ${script} failed (exit ${result.status}):\n${result.stdout}\n${result.stderr}`);
    }

    return result.stdout;
}

/** The same, started without waiting — for fixtures that must overlap in time. */
function seedAsync(script, ...args) {
    return new Promise((resolve, reject) => {
        const child = spawn(PHP, ['tests/e2e/fixtures/run.php', script, ...args], {
            cwd: ROOT, env: process.env, shell: true,
        });
        let out = '';
        child.stdout.on('data', (d) => { out += d; });
        child.stderr.on('data', (d) => { out += d; });
        child.on('exit', (code) => (code === 0 ? resolve(out) : reject(new Error(`fixture ${script} failed (exit ${code}):\n${out}`))));
    });
}

module.exports = { seed, seedAsync };
