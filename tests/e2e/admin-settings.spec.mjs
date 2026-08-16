import { expect, test } from '@playwright/test';

const adminPage = '/wp-admin/admin.php?page=seo-and-social';

test.describe('Seo & Social admin settings', () => {
  test('opens every settings tab through stable test ids', async ({ page }) => {
    await page.goto(adminPage);
    await expect(page.getByTestId('sas-admin-page')).toBeVisible();

    for (const tab of ['social', 'seo', 'llms', 'settings']) {
      await page.getByTestId(`sas-tab-${tab}`).click();
      await expect(page).toHaveURL(new RegExp(`[?&]tab=${tab}(?:&|$)`));
      await expect(page.getByTestId('sas-settings-form')).toBeVisible();
    }
  });

  test('persists values across tabs and reloads', async ({ page }) => {
    const email = `qa-${Date.now()}@example.test`;
    const siteName = `Seo & Social QA ${Date.now()}`;

    await page.goto(adminPage);
    await page.getByTestId('sas_sas_settings_social__email_').fill(email);
    await page.getByTestId('sas-save-settings').click();
    await expect(page.getByTestId('sas-notice-saved')).toBeVisible();

    await page.getByTestId('sas-tab-seo').click();
    await page.getByTestId('sas_sas_settings_seo__site_name_').fill(siteName);
    await page.getByTestId('sas-save-settings').click();
    await expect(page.getByTestId('sas-notice-saved')).toBeVisible();
    await page.reload();
    await expect(page.getByTestId('sas_sas_settings_seo__site_name_')).toHaveValue(siteName);

    await page.getByTestId('sas-tab-social').click();
    await expect(page.getByTestId('sas_sas_settings_social__email_')).toHaveValue(email);
  });

  test('shows a warning when invalid custom schema JSON is submitted', async ({ page }) => {
    await page.goto(`${adminPage}&tab=seo`);
    await page.getByTestId('sas_sas_settings_seo__custom_schema_json_').fill('{invalid-json');
    await page.getByTestId('sas-save-settings').click();

    await expect(page.getByTestId('sas-notice-json-error')).toBeVisible();
  });
});
