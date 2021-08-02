import apiFetch from '@wordpress/api-fetch';
import Cookies from 'js-cookie';
import { nanoid } from 'nanoid';

import doNotTrack from './doNotTrack';

function getCookieName(testId: string): string {
  return `ab-testing-for-wp_${testId}`;
}

function loadTestHTML(test: Element, html: string): void {
  test.innerHTML = html;
  test.classList.add('loaded');
}

function handleTestRender(): void {
  if (doNotTrack()) return;

  const testsOnPage = document.getElementsByClassName('ABTestWrapper');

  for (let i = 0; i < testsOnPage.length; i += 1) {
    const test = testsOnPage[i];
    const testId = (test.getAttribute('data-test')) || '';
    const cookieName = getCookieName(testId);
    const localStorageKey = `${cookieName}_html`;
    const html = window.localStorage.getItem(localStorageKey);

    if (html) {
      loadTestHTML(test, html);
    } else {
      let hasCookie = false;
      let path = `ab-testing-for-wp/v1/ab-test?test=${testId}&nocache=${nanoid()}`;
      const variantId: string = Cookies.get(cookieName);

      if (variantId) {
        hasCookie = true;
        // Append variant param to get the same HTML the user saw when they were cookied.
        path += `&variant=${variantId}`;
      }

      // get variant from server
      apiFetch({ path })
        .then((variant: TestVariant) => {
          if (variant.html) {
            // Swap out the element's html with the selected variant's html.
            loadTestHTML(test, variant.html);
            // Store the variant's html in localStorage for quick retrieval next time.
            window.localStorage.setItem(localStorageKey, variant.html);
            // Set a cookie with the variant id, if it hasn't already been set.
            if (!hasCookie) {
              Cookies.set(cookieName, variant.id, { expires: 30 });
            }
          }
        });
    }
  }
}

export default handleTestRender;
