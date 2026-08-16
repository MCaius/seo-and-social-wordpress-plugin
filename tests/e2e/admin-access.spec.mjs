import { expect, test } from '@playwright/test';

const adminPage = '/wp-admin/admin.php?page=seo-and-social';

const deniedUsers = [
  ['Editor', 'editor.json'],
  ['Author', 'author.json'],
  ['Contributor', 'contributor.json'],
  ['Subscriber', 'subscriber.json'],
];

for (const [role, storageState] of deniedUsers) {
  test.describe(`${role} access`, () => {
    test.use({ storageState: `tests/e2e/.auth/${storageState}` });

    test(`denies the ${role} role on a direct plugin URL`, async ({ page }) => {
      const response = await page.goto(adminPage);

      expect(response?.ok()).toBe(false);
      await expect(page.getByTestId('sas-admin-page')).toHaveCount(0);
    });
  });
}

test.describe('Filtered custom-role access', () => {
  test.use({ storageState: 'tests/e2e/.auth/custom.json' });

  test('allows the documented filtered role without exposing administrator settings', async ({ page }) => {
    const response = await page.goto(adminPage);

    expect(response?.ok()).toBe(true);
    await expect(page.getByTestId('sas-admin-page')).toBeVisible();
    await expect(page.getByTestId('sas-tab-social')).toBeVisible();
    await expect(page.getByTestId('sas-tab-seo')).toBeVisible();
    await expect(page.getByTestId('sas-tab-llms')).toBeVisible();
    await expect(page.getByTestId('sas-tab-settings')).toHaveCount(0);
  });

  test('falls back safely when the filtered role requests the settings tab directly', async ({ page }) => {
    const response = await page.goto(`${adminPage}&tab=settings`);

    expect(response?.ok()).toBe(true);
    await expect(page.getByTestId('sas-admin-page')).toBeVisible();
    await expect(page.getByTestId('sas-tab-social')).toBeVisible();
    await expect(page.getByTestId('sas-tab-settings')).toHaveCount(0);
  });
});
