import apiFetch from '@wordpress/api-fetch';
import { nanoid } from 'nanoid';
import doNotTrack from './doNotTrack';
import { getCookieName, getCookieData, setCookieData } from './cookieData';

export default class ABTest implements ABTestInterface {
  blockEl: HTMLElement;

  testId: string | undefined;

  cookieName?: string;

  localStorageKey?: string;

  variantId?: string | number;

  variant?: TestVariant;

  doNotTrack: boolean;

  constructor(blockEl: HTMLElement) {
    this.blockEl = blockEl;
    this.doNotTrack = doNotTrack();
    this.testId = this.blockEl.dataset.test;
    if (this.testId) {
      this.cookieName = getCookieName(this.testId);
      this.localStorageKey = `${this.cookieName}_html`;
    }

    this.handleRender();
  }

  handleRender = async (): Promise<boolean> => {
    let html = '';
    let hasCookie = false;

    if (!this.doNotTrack && this.localStorageKey) {
      html = window.localStorage.getItem(this.localStorageKey) || '';
    }

    if (!html && this.testId) {
      let path = `ab-testing-for-wp/v1/ab-test?test=${this.testId}&nocache=${nanoid()}`;

      if (!this.doNotTrack) {
        const { variantId } = getCookieData(this.testId);
        if (variantId) {
          hasCookie = true;
          // Append variant param to get the same HTML the user saw when they were cookied.
          path += `&variant=${variantId}`;
        }
      }

      // get variant from server
      this.variant = await apiFetch({ path });
      if (this.variant) {
        html = this.variant.html;
      }
    }

    if (html) {
      // Update the block's html with the chosen variant.
      this.blockEl.innerHTML = html;

      if (!this.doNotTrack) {
        // Store the variant's html in localStorage for quick retrieval next time.
        if (this.localStorageKey) {
          window.localStorage.setItem(this.localStorageKey, html);
        }

        // Set a cookie with the variant id, if it hasn't already been set.
        if (!hasCookie && this.cookieName && this.variant) {
          setCookieData(this.testId, this.variant.id);
        }
      }
    }

    // Add .loaded class so the block is visible to the user.
    this.blockEl.classList.add('loaded');

    return true;
  }

  handleTracking = (): void => {

  }
}
