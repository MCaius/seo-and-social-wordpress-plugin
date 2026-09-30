import { execFileSync } from 'node:child_process';
import { mkdir } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium, expect } from '@playwright/test';

import globalSetup from '../tests/e2e/global-setup.mjs';

const baseURL = process.env.SAS_E2E_BASE_URL || 'http://localhost:8893';
const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const outputDirectory = path.join(projectRoot, 'docs', 'images');
const adminPage = '/wp-admin/admin.php?page=seo-and-social';

function runWp(...args) {
  const npx = process.platform === 'win32' ? 'npx.cmd' : 'npx';

  execFileSync(
    npx,
    ['wp-env', '--config=.wp-env.e2e.json', 'run', 'cli', 'wp', ...args],
    { cwd: projectRoot, stdio: 'inherit' },
  );
}

async function capture(locator, filename) {
  await locator.scrollIntoViewIfNeeded();
  await locator.screenshot({
    animations: 'disabled',
    caret: 'hide',
    path: path.join(outputDirectory, filename),
  });
}

async function fillByLabel(page, label, value) {
  await page.getByLabel(label, { exact: true }).fill(value);
}

async function hideFixedAdminChrome(page) {
  await page.addStyleTag({
    content: `
      #wpadminbar,
      .edit-post-header {
        display: none !important;
      }

      html.wp-toolbar {
        padding-top: 0 !important;
      }
    `,
  });
}

async function prepareSettingsPage(page, tab) {
  const url = tab === 'social' ? adminPage : `${adminPage}&tab=${tab}`;

  await page.goto(url);
  await expect(page.getByTestId('sas-admin-page')).toBeVisible();
  await hideFixedAdminChrome(page);
}

async function captureGeneralSettings(page) {
  await prepareSettingsPage(page, 'settings');
  await capture(page.getByTestId('sas-admin-page'), 'general-settings.png');
}

async function captureSocialSettings(page) {
  await prepareSettingsPage(page, 'social');
  await fillByLabel(page, 'Primary email', 'hello@example.com');
  await fillByLabel(page, 'Phone / WhatsApp', '+1 202 555 0147');
  await fillByLabel(page, 'Facebook URL', 'https://facebook.com/example');
  await fillByLabel(page, 'Instagram URL', 'https://instagram.com/example');
  await fillByLabel(page, 'TikTok URL', 'https://tiktok.com/@example');
  await fillByLabel(page, 'YouTube URL', 'https://youtube.com/@example');

  await page.getByTestId('sas-add-extra-social-link').click();
  const extraLink = page.getByTestId('sas-extra-social-row').last();
  await extraLink.getByTestId('sas-extra-social-key').fill('linkedin');
  await extraLink.getByTestId('sas-extra-social-label').fill('LinkedIn');
  await extraLink.getByTestId('sas-extra-social-url').fill('https://linkedin.com/company/example');

  await capture(page.getByTestId('sas-admin-page'), 'social-settings.png');
}

async function captureSeoSettings(page) {
  await prepareSettingsPage(page, 'seo');
  await fillByLabel(page, 'Site name', 'Example Studio');
  await fillByLabel(page, 'Default meta title', 'Example Studio — Thoughtful digital experiences');
  await fillByLabel(
    page,
    'Default meta description',
    'A concise global description used when a page does not provide its own SEO summary.',
  );
  await fillByLabel(page, 'Business / organization schema type', 'Organization');
  await fillByLabel(page, 'Organization name', 'Example Studio');
  await fillByLabel(page, 'Website URL', 'https://example.com');
  await fillByLabel(page, 'Address', '123 Example Street');
  await fillByLabel(page, 'City', 'Bucharest');
  await fillByLabel(page, 'Country', 'Romania');
  await fillByLabel(page, 'Postal code', '010101');
  await fillByLabel(page, 'Opening hours', 'Mo-Fr 09:00-18:00');
  await fillByLabel(page, 'Price range', '$$');

  await page.getByTestId('sas-add-extra-schema-property').click();
  const schemaProperty = page.getByTestId('sas-extra-schema-row').last();
  await schemaProperty.getByTestId('sas-extra-schema-key').fill('sameAs');
  await schemaProperty.getByTestId('sas-extra-schema-type').selectOption('url');
  await schemaProperty.getByTestId('sas-extra-schema-value').fill('https://www.linkedin.com/company/example');

  await capture(page.getByTestId('sas-admin-page'), 'global-seo-settings.png');
}

async function openMetaBoxes(page, postId) {
  await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);

  await page.evaluate(() => {
    const preferences = window.wp?.data?.select('core/preferences');
    const preferenceActions = window.wp?.data?.dispatch('core/preferences');

    if (preferences?.get('core/edit-post', 'welcomeGuide')) {
      preferenceActions.set('core/edit-post', 'welcomeGuide', false);
      return;
    }

    const editPost = window.wp?.data?.select('core/edit-post');
    const editPostActions = window.wp?.data?.dispatch('core/edit-post');

    if (editPost?.isFeatureActive?.('welcomeGuide')) {
      editPostActions.toggleFeature('welcomeGuide');
    }
  });

  const seoMetaBox = page.getByTestId('sas-seo-meta-box');

  if (!(await seoMetaBox.isVisible())) {
    const metaBoxesAreaToggle = page.getByTestId('sas-toggle-meta-boxes-area');

    await expect(metaBoxesAreaToggle).toBeVisible();
    if ((await metaBoxesAreaToggle.getAttribute('aria-expanded')) !== 'true') {
      await metaBoxesAreaToggle.press('Enter');
    }
  }

  for (const [metaBox, toggleTestId] of [
    [page.getByTestId('sas-faq-meta-box'), 'sas-toggle-faq-meta-box'],
    [seoMetaBox, 'sas-toggle-seo-meta-box'],
  ]) {
    if (!(await metaBox.isVisible())) {
      await page.getByTestId(toggleTestId).press('Enter');
    }

    await expect(metaBox).toBeVisible();
  }
}

async function fillFaqAnswer(page, index, answer) {
  const textarea = page.getByTestId('sas-faq-answer').nth(index);

  await expect.poll(() => textarea.evaluate((element) => (
    element.dataset.editorInitialized === 'true'
      && Boolean(window.tinyMCE?.get(element.id)?.initialized)
  ))).toBe(true);

  await textarea.evaluate((element, content) => {
    const editor = window.tinyMCE.get(element.id);

    element.value = content;
    editor.setContent(content);
    editor.save();
  }, answer);
}

async function capturePageSeoAndFaq(page, context) {
  const response = await context.request.get('/wp-json/wp/v2/pages?slug=sas-e2e-seo&_fields=id');
  const posts = await response.json();

  if (!response.ok() || posts.length !== 1) {
    throw new Error('Could not locate the deterministic README screenshot page.');
  }

  await openMetaBoxes(page, posts[0].id);
  await hideFixedAdminChrome(page);
  await page.getByTestId('sas_seo_title').fill('A clear, page-specific SEO title');
  await page.getByTestId('sas_seo_canonical_url').fill('https://example.com/services/');
  await page.getByTestId('sas_seo_description').fill(
    'A concise description that overrides the global SEO default for this page.',
  );
  await page.getByTestId('sas_seo_robots').selectOption('index,follow');
  await page.getByTestId('sas_seo_schema_type').fill('WebPage');

  for (const [question, answer, position] of [
    ['What does this service include?', '<p>Strategy, implementation, and ongoing support.</p>', '10'],
    ['How quickly can we get started?', '<p>Most projects can begin within two weeks.</p>', '20'],
  ]) {
    await page.getByTestId('sas-add-faq-item').click();
    const index = (await page.getByTestId('sas-faq-row').count()) - 1;
    const row = page.getByTestId('sas-faq-row').nth(index);
    const toggle = row.getByTestId('sas-toggle-faq-row');

    if ((await toggle.getAttribute('aria-expanded')) !== 'true') {
      await toggle.press('Enter');
    }
    await row.getByTestId('sas-faq-question').fill(question);
    await row.getByTestId('sas-faq-position').fill(position);
    await fillFaqAnswer(page, index, answer);

    if (index > 0) {
      await toggle.press('Enter');
    }
  }

  await page.evaluate(() => {
    const findVisible = (testId) => Array.from(document.querySelectorAll(`[data-testid="${testId}"]`))
      .find((element) => element.getClientRects().length > 0);
    const faqPostbox = findVisible('sas-faq-meta-box')?.closest('.postbox');
    const seoPostbox = findVisible('sas-seo-meta-box')?.closest('.postbox');

    if (!faqPostbox || !seoPostbox) {
      throw new Error('Could not locate the rendered SEO and FAQ postboxes.');
    }

    const metaBoxes = document.createElement('div');
    metaBoxes.id = 'sas-readme-meta-boxes';
    metaBoxes.className = 'edit-post-meta-boxes-area is-normal';
    metaBoxes.style.boxSizing = 'border-box';
    metaBoxes.style.display = 'grid';
    metaBoxes.style.gap = '24px';
    metaBoxes.style.padding = '24px';
    metaBoxes.style.width = '1160px';
    metaBoxes.style.background = '#f0f0f1';

    for (const postbox of [faqPostbox, seoPostbox]) {
      postbox.style.margin = '0';
      metaBoxes.append(postbox);
    }

    document.body.append(metaBoxes);

    const answer = faqPostbox.querySelector('[data-testid="sas-faq-answer"]');
    const editorBody = faqPostbox.querySelector('.mce-edit-area iframe')?.contentDocument?.body;

    if (answer && editorBody) {
      editorBody.innerHTML = answer.value;
    }
  });

  const metaBoxes = page.locator('#sas-readme-meta-boxes');
  await expect(metaBoxes).toBeVisible();
  await capture(metaBoxes, 'page-specific-seo-faq.png');
}

async function main() {
  await mkdir(outputDirectory, { recursive: true });
  await globalSetup();
  runWp(
    'eval',
    'delete_option( "sas_settings" ); $post = get_page_by_path( "sas-e2e-seo", OBJECT, "page" ); if ( $post ) { delete_post_meta( $post->ID, "_sas_seo_overrides" ); delete_post_meta( $post->ID, "_sas_faq_items" ); }',
  );

  const browser = await chromium.launch();
  const context = await browser.newContext({
    baseURL,
    colorScheme: 'light',
    deviceScaleFactor: 1,
    storageState: path.join(projectRoot, 'tests', 'e2e', '.auth', 'admin.json'),
    viewport: { width: 1440, height: 1000 },
  });
  const page = await context.newPage();

  try {
    await captureGeneralSettings(page);
    await captureSocialSettings(page);
    await captureSeoSettings(page);
    await capturePageSeoAndFaq(page, context);
  } finally {
    await context.close();
    await browser.close();
  }
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
