// The one statement of what an e2e run is. serve.js gives it to the server and
// the worker; playwright.config.js puts it on the Playwright process, so every
// fixture a spec shells out to inherits the same database. In `daftar` the two
// halves disagreed twice, and the fixtures wrote into the development database
// while the browser read the copy.
//
// SESSION_DRIVER=database because a password reset logs out other devices by
// deleting `sessions` rows; on any other driver that silently does nothing.

const E2E_ENV = {
    APP_ENV: 'e2e',
    APP_DEBUG: 'true',
    DB_CONNECTION: 'mysql',
    DB_DATABASE: 'undilotlook_e2e',
    SESSION_DRIVER: 'database',
    QUEUE_CONNECTION: 'database',
    CACHE_STORE: 'database',
    MAIL_MAILER: 'log',
};

module.exports = { E2E_ENV };
