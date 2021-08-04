import Cookies from 'js-cookie';

export function getCookieName(testId: string): string {
  return `ab-testing-for-wp_${testId}`;
}

export function getCookieData(testId: string | undefined): CookieData {
  if (testId) {
    const cookie: string | undefined = Cookies.get(getCookieName(testId));
    if (cookie) {
      const [variantId, tracked] = cookie.split(':');
      return { variantId, tracked };
    }
  }

  return {
    variantId: '',
    tracked: '',
  };
}

export function setCookieData(testId: string | undefined, variantId: string | undefined, tracked = ''): void {
  if (testId && variantId) {
    const cookieValue = [variantId, tracked].filter((item) => !!item).join(':');
    Cookies.set(getCookieName(testId), cookieValue, { expires: 30 });
  }
}
