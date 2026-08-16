import { expect, test } from '@playwright/test';

const settingsPage = '/wp-admin/admin.php?page=seo-and-social&tab=settings';

test.describe('OG image maintenance actions', () => {
  test.describe.configure({ mode: 'serial' });

  test('regenerates OG images and reports generated, failed, and skipped totals', async ({ page }) => {
    await page.goto(settingsPage);
    await expect(page.getByTestId('sas-admin-page')).toBeVisible();
    await page.getByTestId('sas-regenerate-og-images').click();

    await expect(page.getByTestId('sas-maintenance-notice')).toContainText(
      /OG images regenerated\. Generated: \d+\. Failed: \d+\. Skipped: \d+\./,
    );
  });

  test('confirms deletion and reports the deleted generated-image total', async ({ page }) => {
    await page.goto(settingsPage);
    await expect(page.getByTestId('sas-admin-page')).toBeVisible();

    page.once('dialog', (dialog) => dialog.accept());
    await page.getByTestId('sas-delete-og-images').click();

    await expect(page.getByTestId('sas-maintenance-notice')).toContainText(
      /Generated WebP OG images deleted: \d+\./,
    );
  });
});
