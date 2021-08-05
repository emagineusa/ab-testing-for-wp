import apiFetch from '@wordpress/api-fetch';
import { nanoid } from 'nanoid';
import doNotTrack from './doNotTrack';
import { getCookieName, getCookieData, setCookieData } from './cookieData';

export default class ABTest implements ABTestInterface {
  blockEl: HTMLElement;

  testId: string | undefined;

  controlVariantId: string | undefined;

  goal: string | undefined;

  goalType: string | undefined;

  cookieName?: string;

  variantId?: string;

  variant?: TestVariant;

  doNotTrack: boolean;

  constructor(blockEl: HTMLElement) {
    this.blockEl = blockEl;
    this.doNotTrack = doNotTrack();
    this.testId = this.blockEl.dataset.test;
    this.controlVariantId = this.blockEl.dataset.control;
    this.goal = this.blockEl.dataset.goal;
    this.goalType = this.blockEl.dataset.goalType;
    if (this.testId) {
      this.cookieName = getCookieName(this.testId);
    }

    this.handleRender()
      .then(() => {
        this.handleTracking();
      });
  }

  handleRender = async (): Promise<boolean> => {
    if (this.testId && !this.doNotTrack) {
      let hasCookie = false;
      let path = `ab-testing-for-wp/v1/ab-test?test=${this.testId}&nocache=${nanoid()}`;
      const { variantId } = getCookieData(this.testId);
      if (variantId) {
        hasCookie = true;
        // Append variant param to get the same HTML the user saw when they were cookied.
        path += `&variant=${variantId}`;
      }

      // Get variant from server.
      this.variant = await apiFetch({ path });

      // If we have a variant, and it's not the control, then update the html.
      if (this.variant && this.variant.id !== this.controlVariantId) {
        this.blockEl.innerHTML = this.variant.html;
      }

      // Set a cookie with the variant id, if it hasn't already been set.
      if (!hasCookie && this.cookieName && this.variant) {
        setCookieData(this.testId, this.variant.id, 'P');
      }
    }

    // Add .loaded class so the block is visible to the user.
    this.blockEl.classList.add('loaded');

    return true;
  }

  trackLink = async (url: string | undefined): Promise<boolean> => {
    if (url) {
      const { variantId, tracked } = getCookieData(this.testId);
      if (tracked !== 'C') {
        const isTrackedInDB = await apiFetch({
          path: `ab-testing-for-wp/v1/track?nocache=${nanoid()}`,
          method: 'POST',
          data: {
            url,
            variantId,
            goal: this.goal,
            goalType: this.goalType,
          },
        });

        if (isTrackedInDB && this.cookieName && this.variant) {
          // Set a cookie with the variant id, if it hasn't already been set.
          setCookieData(this.testId, this.variant.id, 'C');
        }
      }
    }

    return true;
  }

  handleTracking = (): void => {
    if (doNotTrack()) {
      return;
    }

    const anchors = Array.from(this.blockEl.querySelectorAll('a'));
    anchors.forEach((anchor) => {
      anchor.addEventListener('click', (e) => {
        e.preventDefault();
        this.trackLink(anchor.href)
          .then(() => {
            window.location = anchor.href;
          });
      });
    });

    const forms = Array.from(this.blockEl.querySelectorAll('form'));
    forms.forEach((form) => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        this.trackLink(form.action);
      });
    });
  }
}
