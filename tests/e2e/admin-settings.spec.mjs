import { expect, test } from '@playwright/test';

const adminPage = '/wp-admin/admin.php?page=seo-and-social';

test.describe('Seo & Social admin settings', () => {
  test.describe.configure({ mode: 'serial' });

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

  test('validates and persists Extra social links rows', async ({ page }) => {
    const suffix = Date.now();
    const socialKey = `community_${suffix}`;
    const socialLabel = `Community ${suffix}`;
    const socialUrl = `https://example.test/community-${suffix}`;

    await page.goto(adminPage);
    await page.getByTestId('sas-add-extra-social-link').click();

    const socialRow = page.getByTestId('sas-extra-social-row').last();
    const keyInput = socialRow.getByTestId('sas-extra-social-key');
    const labelInput = socialRow.getByTestId('sas-extra-social-label');
    const urlInput = socialRow.getByTestId('sas-extra-social-url');

    await labelInput.fill(socialLabel);
    await urlInput.fill(socialUrl);
    await page.getByTestId('sas-save-settings').click();
    await expect(keyInput).toBeFocused();
    expect(await keyInput.evaluate((input) => input.validity.valueMissing)).toBe(true);

    await keyInput.fill(socialKey);
    await labelInput.fill('');
    await page.getByTestId('sas-save-settings').click();
    await expect(labelInput).toBeFocused();
    expect(await labelInput.evaluate((input) => input.validity.valueMissing)).toBe(true);

    await labelInput.fill(socialLabel);
    await urlInput.fill('');
    await page.getByTestId('sas-save-settings').click();
    await expect(urlInput).toBeFocused();
    expect(await urlInput.evaluate((input) => input.validity.valueMissing)).toBe(true);

    await urlInput.fill(`example.test/community-${suffix}`);
    await page.getByTestId('sas-save-settings').click();
    await expect(urlInput).toBeFocused();
    expect(await urlInput.evaluate((input) => input.validity.customError)).toBe(true);

    await urlInput.fill(socialUrl);
    await page.getByTestId('sas-add-extra-social-link').click();

    const duplicateSocialRow = page.getByTestId('sas-extra-social-row').last();
    const duplicateSocialKey = duplicateSocialRow.getByTestId('sas-extra-social-key');

    await duplicateSocialKey.fill(socialKey);
    await duplicateSocialRow.getByTestId('sas-extra-social-label').fill('Duplicate social key');
    await duplicateSocialRow.getByTestId('sas-extra-social-url').fill(`https://example.test/duplicate-${suffix}`);
    await page.getByTestId('sas-save-settings').click();
    await expect(duplicateSocialKey).toBeFocused();
    expect(await duplicateSocialKey.evaluate((input) => input.validity.customError)).toBe(true);

    await duplicateSocialRow.getByTestId('sas-remove-extra-social-row').click();
    await page.getByTestId('sas-save-settings').click();
    await expect(page.getByTestId('sas-notice-saved')).toBeVisible();
    await page.reload();

    expect(await page.getByTestId('sas-extra-social-key').evaluateAll((inputs) => inputs.map((input) => input.value))).toContain(socialKey);
    expect(await page.getByTestId('sas-extra-social-label').evaluateAll((inputs) => inputs.map((input) => input.value))).toContain(socialLabel);
    expect(await page.getByTestId('sas-extra-social-url').evaluateAll((inputs) => inputs.map((input) => input.value))).toContain(socialUrl);
  });

  test('shows a warning when invalid custom schema JSON is submitted', async ({ page }) => {
    await page.goto(`${adminPage}&tab=seo`);
    await page.getByTestId('sas_sas_settings_seo__custom_schema_json_').fill('{invalid-json');
    await page.getByTestId('sas-save-settings').click();

    await expect(page.getByTestId('sas-notice-json-error')).toBeVisible();
  });

  test('validates and persists Extra schema property rows', async ({ page }) => {
    const suffix = Date.now();
    const propertyKey = `qaProperty${suffix}`;
    const propertyValue = `QA value ${suffix}`;

    await page.goto(`${adminPage}&tab=seo`);
    await page.getByTestId('sas-add-extra-schema-property').click();

    const schemaRow = page.getByTestId('sas-extra-schema-row').last();
    const keyInput = schemaRow.getByTestId('sas-extra-schema-key');
    const valueInput = schemaRow.getByTestId('sas-extra-schema-value');

    await valueInput.fill(propertyValue);
    await page.getByTestId('sas-save-settings').click();
    await expect(keyInput).toBeFocused();
    expect(await keyInput.evaluate((input) => input.validity.valueMissing)).toBe(true);

    await keyInput.fill(propertyKey);
    await valueInput.fill('');
    await page.getByTestId('sas-save-settings').click();
    await expect(valueInput).toBeFocused();
    expect(await valueInput.evaluate((input) => input.validity.valueMissing)).toBe(true);

    await valueInput.fill(propertyValue);
    await page.getByTestId('sas-save-settings').click();
    await expect(page.getByTestId('sas-notice-saved')).toBeVisible();
    await page.reload();

    expect(await page.getByTestId('sas-extra-schema-key').evaluateAll((inputs) => inputs.map((input) => input.value))).toContain(propertyKey);
    expect(await page.getByTestId('sas-extra-schema-value').evaluateAll((inputs) => inputs.map((input) => input.value))).toContain(propertyValue);
  });

	test('validates schema URLs and blocks exact duplicate rows', async ({ page }) => {
    const suffix = Date.now();
    const propertyKey = `qaUrl${suffix}`;
    const firstUrl = `https://example.test/schema-${suffix}`;
    const secondUrl = `https://example.test/schema-${suffix}-alternate`;

    await page.goto(`${adminPage}&tab=seo`);
    await page.getByTestId('sas-add-extra-schema-property').click();

    const firstRow = page.getByTestId('sas-extra-schema-row').last();
    const firstValue = firstRow.getByTestId('sas-extra-schema-value');

    await firstRow.getByTestId('sas-extra-schema-key').fill(propertyKey);
    await firstRow.getByTestId('sas-extra-schema-type').selectOption('url');
    await firstValue.fill(`example.test/schema-${suffix}`);
    await page.getByTestId('sas-save-settings').click();

    await expect(firstValue).toBeFocused();
    expect(await firstValue.evaluate((input) => input.validity.customError)).toBe(true);
    await expect(page.getByTestId('sas-notice-saved')).toHaveCount(0);

    await firstValue.fill(firstUrl);

    let duplicateRow;

    for (const value of [firstUrl, secondUrl]) {
      await page.getByTestId('sas-add-extra-schema-property').click();
	  const rowIndex = (await page.getByTestId('sas-extra-schema-row').count()) - 1;
	  const row = page.getByTestId('sas-extra-schema-row').nth(rowIndex);

      await row.getByTestId('sas-extra-schema-key').fill(propertyKey);
      await row.getByTestId('sas-extra-schema-type').selectOption('url');
      await row.getByTestId('sas-extra-schema-value').fill(value);

      if (value === firstUrl) {
        duplicateRow = row;
      }
    }

    await page.getByTestId('sas-save-settings').click();

    const duplicateValue = duplicateRow.getByTestId('sas-extra-schema-value');

    await expect(duplicateValue).toBeFocused();
    await expect(duplicateValue).toHaveJSProperty(
      'validationMessage',
      'This row duplicates an existing schema property. Change or remove it before saving.',
    );
    await expect(page.getByTestId('sas-notice-schema-duplicate')).toHaveCount(0);

    await duplicateRow.getByTestId('sas-remove-extra-schema-row').click();
    await page.getByTestId('sas-save-settings').click();
    await expect(page.getByTestId('sas-notice-saved')).toBeVisible();
    await page.reload();

	const savedKeys = await page.getByTestId('sas-extra-schema-key').evaluateAll((inputs) => inputs.map((input) => input.value));
	const savedValues = await page.getByTestId('sas-extra-schema-value').evaluateAll((inputs) => inputs.map((input) => input.value));
	const matchingValues = savedValues.filter((value, index) => savedKeys[index] === propertyKey);

    expect(matchingValues).toEqual([firstUrl, secondUrl]);
  });

  test('validates and persists LLMs dynamic rows', async ({ page }) => {
    const suffix = Date.now();
    const recommendedLabel = `Recommended page ${suffix}`;
    const recommendedUrl = `https://example.test/recommended-${suffix}`;
    const ignoredLabel = `Ignored section ${suffix}`;

    await page.goto(`${adminPage}&tab=llms`);
    await page.getByTestId('sas-add-llms-recommended-page').click();

    const recommendedRow = page.getByTestId('sas-llms-recommended-page-row').last();
    const recommendedLabelInput = recommendedRow.getByTestId('sas-llms-recommended-page-label');

    await recommendedRow.getByTestId('sas-llms-recommended-page-url').fill(recommendedUrl);
    await recommendedRow.getByTestId('sas-llms-recommended-page-note').fill('Optional note');
    await page.getByTestId('sas-save-settings').click();

    await expect(recommendedLabelInput).toBeFocused();
    expect(await recommendedLabelInput.evaluate((input) => input.validity.valueMissing)).toBe(true);
    await expect(page.getByTestId('sas-notice-saved')).toHaveCount(0);

    await recommendedLabelInput.fill(recommendedLabel);
    const recommendedUrlInput = recommendedRow.getByTestId('sas-llms-recommended-page-url');

    await recommendedUrlInput.fill(`example.test/recommended-${suffix}`);
    await page.getByTestId('sas-save-settings').click();
    await expect(recommendedUrlInput).toBeFocused();
    expect(await recommendedUrlInput.evaluate((input) => input.validity.customError)).toBe(true);

    await recommendedUrlInput.fill(recommendedUrl);
    await page.getByTestId('sas-add-llms-recommended-page').click();

    const duplicateRecommendedRow = page.getByTestId('sas-llms-recommended-page-row').last();
    const duplicateRecommendedUrl = duplicateRecommendedRow.getByTestId('sas-llms-recommended-page-url');

    await duplicateRecommendedRow.getByTestId('sas-llms-recommended-page-label').fill('Duplicate recommended URL');
    await duplicateRecommendedUrl.fill(recommendedUrl);
    await page.getByTestId('sas-save-settings').click();
    await expect(duplicateRecommendedUrl).toBeFocused();
    expect(await duplicateRecommendedUrl.evaluate((input) => input.validity.customError)).toBe(true);

    await duplicateRecommendedRow.getByTestId('sas-remove-llms-recommended-page-row').click();
    await page.getByTestId('sas-add-llms-ignored-section').click();

    const ignoredRow = page.getByTestId('sas-llms-ignored-section-row').last();
    const ignoredLabelInput = ignoredRow.getByTestId('sas-llms-ignored-section-label');

    await ignoredRow.getByTestId('sas-llms-ignored-section-note').fill('Optional description');
    await page.getByTestId('sas-save-settings').click();

    await expect(ignoredLabelInput).toBeFocused();
    expect(await ignoredLabelInput.evaluate((input) => input.validity.valueMissing)).toBe(true);
    await expect(page.getByTestId('sas-notice-saved')).toHaveCount(0);

    await ignoredLabelInput.fill(ignoredLabel);
    await page.getByTestId('sas-save-settings').click();
    await expect(page.getByTestId('sas-notice-saved')).toBeVisible();
    await page.reload();

    expect(
      await page.getByTestId('sas-llms-recommended-page-label').evaluateAll((inputs) => inputs.map((input) => input.value)),
    ).toContain(recommendedLabel);
    expect(
      await page.getByTestId('sas-llms-recommended-page-url').evaluateAll((inputs) => inputs.map((input) => input.value)),
    ).toContain(recommendedUrl);
    expect(
      await page.getByTestId('sas-llms-ignored-section-label').evaluateAll((inputs) => inputs.map((input) => input.value)),
    ).toContain(ignoredLabel);
  });

  test('keeps stored untrusted settings inert in admin output', async ({ page }) => {
    await page.goto(`${adminPage}&tab=seo`);
    await page.getByTestId('sas_sas_settings_seo__site_name_').fill(
      '<img src=x onerror="window.__sasAdminExecuted=true">Safe site name',
    );
    await page.getByTestId('sas-save-settings').click();

    await expect(page.getByTestId('sas-notice-saved')).toBeVisible();
    await page.reload();
    await expect(page.getByTestId('sas_sas_settings_seo__site_name_')).toHaveValue('Safe site name');
    expect(await page.evaluate(() => window.__sasAdminExecuted)).toBeUndefined();
  });
});
