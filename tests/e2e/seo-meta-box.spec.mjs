import { expect, test } from '@playwright/test';

test.describe('SEO meta box', () => {
  test.describe.configure({ mode: 'serial' });

  let postId;

  test.beforeAll(async ({ request }) => {
    const response = await request.get('/wp-json/wp/v2/pages?slug=sas-e2e-seo&_fields=id');

    expect(response.ok()).toBe(true);
    const pages = await response.json();
    expect(pages).toHaveLength(1);
    postId = pages[0].id;
  });

  async function openEditor(page) {
    await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
    const metaBox = page.getByTestId('sas-seo-meta-box');

    if (!(await metaBox.isVisible())) {
      const metaBoxesAreaToggle = page.getByTestId('sas-toggle-meta-boxes-area');

      if (await metaBoxesAreaToggle.isVisible()) {
        await metaBoxesAreaToggle.click();
      }
    }

    if (!(await metaBox.isVisible())) {
      const seoMetaBoxToggle = page.getByTestId('sas-toggle-seo-meta-box');

      if (await seoMetaBoxToggle.isVisible()) {
        await seoMetaBoxToggle.click();
      }
    }

    await expect(metaBox).toBeVisible();
  }

  async function savePost(page) {
    await page.evaluate(async () => {
      const editor = window.wp?.data?.dispatch('core/editor');

      if (!editor) {
        throw new Error('WordPress editor data store is unavailable.');
      }

      await editor.savePost();
    });
  }

  async function getRestSeo(request) {
    const response = await request.get(`/wp-json/wp/v2/pages/${postId}?_fields=seo_overrides`);

    expect(response.ok()).toBe(true);
    const body = await response.json();
    return body.seo_overrides;
  }

  test('creates and updates SEO overrides in the editor and REST output', async ({ page, request }) => {
    const title = `E2E SEO title ${Date.now()}`;
    const description = 'E2E SEO description persisted by Playwright.';
    const canonicalUrl = `http://localhost:8893/canonical-${Date.now()}/`;
    const schemaJson = '{"headline":"E2E schema","active":true}';

    await openEditor(page);
    await page.getByTestId('sas_seo_title').fill(title);
    await page.getByTestId('sas_seo_description').fill(description);
    await page.getByTestId('sas_seo_canonical_url').fill(canonicalUrl);
    await page.getByTestId('sas_seo_robots').selectOption('noindex,nofollow');
    await page.getByTestId('sas_seo_schema_type').fill('WebPage');
    await page.getByTestId('sas_seo_custom_schema_json').fill(schemaJson);
    await savePost(page);

    await page.reload();
    await expect(page.getByTestId('sas_seo_title')).toHaveValue(title);
    await expect(page.getByTestId('sas_seo_description')).toHaveValue(description);
    await expect(page.getByTestId('sas_seo_canonical_url')).toHaveValue(canonicalUrl);
    await expect(page.getByTestId('sas_seo_robots')).toHaveValue('noindex,nofollow');
    await expect(page.getByTestId('sas_seo_schema_type')).toHaveValue('WebPage');
    await expect(page.getByTestId('sas_seo_custom_schema_json')).toHaveValue(schemaJson);

    const seo = await getRestSeo(request);
    expect(seo).toMatchObject({
      seo_title: title,
      seo_description: description,
      canonical_url: canonicalUrl,
      robots: 'noindex,nofollow',
      schema_type: 'WebPage',
      custom_schema_json: schemaJson,
    });
  });

  test('removes invalid JSON and sanitizes stored markup', async ({ page, request }) => {
    await openEditor(page);
    await page.getByTestId('sas_seo_title').fill('<img src=x onerror="window.__sasE2EExecuted=true">Safe title');
    await page.getByTestId('sas_seo_custom_schema_json').fill('{invalid-json');
    await savePost(page);

    await page.reload();
    await expect(page.getByTestId('sas_seo_title')).toHaveValue('Safe title');
    await expect(page.getByTestId('sas_seo_custom_schema_json')).toHaveValue('');
    expect(await page.evaluate(() => window.__sasE2EExecuted)).toBeUndefined();

    const seo = await getRestSeo(request);
    expect(seo.seo_title).toBe('Safe title');
    expect(seo.custom_schema_json).toBe('');
  });

  test('removes SEO overrides from the editor and REST output', async ({ page, request }) => {
    await openEditor(page);
    await page.getByTestId('sas_seo_title').fill('');
    await page.getByTestId('sas_seo_description').fill('');
    await page.getByTestId('sas_seo_og_image_url').fill('');
    await page.getByTestId('sas_seo_canonical_url').fill('');
    await page.getByTestId('sas_seo_robots').selectOption('');
    await page.getByTestId('sas_seo_schema_type').fill('');
    await page.getByTestId('sas_seo_custom_schema_json').fill('');
    await savePost(page);

    await page.reload();
    await expect(page.getByTestId('sas_seo_title')).toHaveValue('');
    await expect(page.getByTestId('sas_seo_description')).toHaveValue('');
    await expect(page.getByTestId('sas_seo_canonical_url')).toHaveValue('');

    const seo = await getRestSeo(request);
    expect(seo).toMatchObject({
      seo_title: '',
      seo_description: '',
      og_image_url: '',
      canonical_url: '',
      robots: '',
      schema_type: '',
      custom_schema_json: '',
    });
  });
});
