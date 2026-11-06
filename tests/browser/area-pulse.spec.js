import { test, expect } from '@playwright/test';

test('Area Pulse illustrates reported news, supports keyboard-friendly area selection', async ({page}) => {
  const errors=[];
  page.on('pageerror',error=>errors.push(error.message));
  await page.goto('/area-map');
  await expect(page.getByRole('heading',{name:/your city,/i})).toBeVisible();
  await expect(page.locator('[data-area-map] [data-map-area]')).toHaveCount(12);
  await expect(page.locator('.map-disclaimer')).toContainText('not GPS navigation or live road congestion');
  await page.locator('[data-area-map] [data-map-area="baner"]').click();
  await expect(page.locator('[data-area-row="baner"]')).toHaveClass(/is-selected/);
  await expect(page.locator('[data-area-map-selection]')).toContainText('Baner');
  const link=page.locator('[data-area-row="baner"] .area-directory-info a').first();
  await expect(link).toHaveAttribute('href',/\/area\/baner/);
  expect(errors).toEqual([]);
});

test('Nearest area suggestion requires explicit consent and manual save', async ({page,context}) => {
  await context.grantPermissions(['geolocation']);
  await context.setGeolocation({latitude:18.559,longitude:73.7898,accuracy:120});
  await page.goto('/profile');
  await expect(page.locator('[data-find-area]')).toBeVisible();
  await page.locator('[data-find-area]').click();
  await expect(page.locator('[data-geo-status]')).toContainText('Suggested Baner');
  await expect(page.locator('input[name="areas"][value="Baner"]')).toBeChecked();
  await page.locator('[data-preferences] button[type="submit"]').click();
  await expect(page.locator('[data-preference-status]')).toHaveText('Saved');
});
