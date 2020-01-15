Cypress.Commands.add('login', () => {
  cy.request({
    url: '/wp-login.php',
    method: 'POST',
    form: true,
    body: {
      log: Cypress.env('WP_USER'),
      pwd: Cypress.env('WP_PASSWORD'),
      rememberme: 'forever',
      testcookie: 1,
    },
  });
});

Cypress.Commands.add('logout', () => {
  // clear all cookies
  cy.getCookies().then((cookies) => {
    cookies.forEach((cookie) => cy.clearCookie(cookie.name));
  });
});

Cypress.Commands.add('visitAdmin', (page = '') => {
  cy.visit(`/wp-admin/${page}`);
});

Cypress.Commands.add('addTestInEditor', () => {
  // open Gutenberg dialog
  cy.get('.edit-post-header-toolbar > :nth-child(1) > .editor-inserter > .components-button').click();

  // search for A/B Testing
  cy.get('.editor-inserter__search').type('A/B Test');

  // insert block
  cy.get('.editor-block-list-item-ab-testing-for-wp-ab-test-block-inserter').click();
});

Cypress.Commands.add('disableTooltips', () => {
  const dataKey = 'WP_DATA_USER_1';

  cy.clearLocalStorage(dataKey).then((storage) => {
    const currentData = JSON.parse(storage.getItem(dataKey) || '{}');

    if (!currentData['core/nux']) {
      currentData['core/nux'] = {};
    }

    if (!currentData['core/nux'].preferences) {
      currentData['core/nux'].preferences = {};
    }

    currentData['core/nux'].preferences = {
      areTipsEnabled: false,
    };

    storage.setItem(dataKey, JSON.stringify(currentData));
  });
});

const nativeInputValueSetter = (Object.getOwnPropertyDescriptor(
  window.HTMLInputElement.prototype,
  'value',
) || { set: (): void => undefined }).set as any;

Cypress.Commands.add('changeRange', (selector: string, value: number) => {
  cy.get(selector).eq(0).then((element) => {
    // natively set
    nativeInputValueSetter.call(element[0], value);

    // now dispatch the event
    element[0].dispatchEvent(new Event('change', { bubbles: true }));
  });
});

Cypress.Commands.add('installWordPress', () => {
  // go to install page
  cy.visitAdmin('install.php');

  cy.get('body').eq(0).then((body) => {
    // select language if on language page
    if (body.hasClass('language-chooser')) {
      cy.get('#language-continue').click();
    }
  });

  // fill out form
  cy.get('#weblog_title').type('A/B Testing for WordPress E2E tests');
  cy.get('#user_login').type(Cypress.env('WP_USER'));
  cy.get('#pass1').clear({ force: true }).type(Cypress.env('WP_PASSWORD'), { force: true });
  cy.get('#admin_email').type('e2e@abtestingforwp.com');
  cy.get('#submit').click();

  // Check if installed
  cy.contains('WordPress has been installed.');
});

Cypress.Commands.add('activatePlugin', () => {
  cy.login();

  // go to plugins page
  cy.visitAdmin('plugins.php');

  // has plugin installed
  cy.contains('A/B Testing for WordPress');

  // activate plugin
  cy.get('[data-slug="ab-testing-for-wp"] > .plugin-title > .row-actions > .activate').click();

  // check if worked
  cy.contains('Plugin activated.');
});

Cypress.Commands.add('wipeInstall', () => {
  cy.exec('npm run e2e:wipe-db');
});

Cypress.Commands.add('cleanInstall', () => {
  cy.exec('npm run e2e:reset-db');
});
