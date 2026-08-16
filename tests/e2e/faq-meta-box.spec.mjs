import { expect, test } from '@playwright/test';

test.describe('FAQ meta box', () => {
  test.describe.configure({ mode: 'serial' });

  let postId;

  test.beforeAll(async ({ request }) => {
    const response = await request.get('/wp-json/wp/v2/pages?slug=sas-e2e-faq&_fields=id');

    expect(response.ok()).toBe(true);
    const pages = await response.json();
    expect(pages).toHaveLength(1);
    postId = pages[0].id;
  });

  async function openEditor(page) {
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

    const metaBox = page.getByTestId('sas-faq-meta-box');

    if (!(await metaBox.isVisible())) {
      const metaBoxesAreaToggle = page.getByTestId('sas-toggle-meta-boxes-area');

      await expect(metaBoxesAreaToggle).toBeVisible();
      await metaBoxesAreaToggle.press('Enter');
      await expect(metaBoxesAreaToggle).toHaveAttribute('aria-expanded', 'true');
    }

    if (!(await metaBox.isVisible())) {
      const faqMetaBoxToggle = page.getByTestId('sas-toggle-faq-meta-box');

      await expect(faqMetaBoxToggle).toBeVisible();
      await faqMetaBoxToggle.press('Enter');
    }

    await expect(metaBox).toBeVisible();
  }

  async function savePost(page) {
    await page.evaluate(async () => {
      if (window.tinyMCE?.triggerSave) {
        window.tinyMCE.triggerSave();
      }

      const editor = window.wp?.data?.dispatch('core/editor');

      if (!editor) {
        throw new Error('WordPress editor data store is unavailable.');
      }

      await editor.savePost();
    });
  }

  async function setAnswer(page, index, value) {
    await page.getByTestId('sas-faq-answer').nth(index).evaluate((textarea, answer) => {
      const editor = window.tinyMCE?.get(textarea.id);

      if (editor) {
        editor.setContent(answer);
        editor.save();
        return;
      }

      textarea.value = answer;
      textarea.dispatchEvent(new Event('input', { bubbles: true }));
      textarea.dispatchEvent(new Event('change', { bubbles: true }));
    }, value);
  }

  async function getRestFaq(request) {
    const response = await request.get(`/wp-json/wp/v2/pages/${postId}?_fields=faq_items`);

    expect(response.ok()).toBe(true);
    const body = await response.json();
    return body.faq_items;
  }

  test('adds rows and exposes only enabled items in position order', async ({ page, request }) => {
    await openEditor(page);

    for (let index = 0; index < 3; index += 1) {
      await page.getByTestId('sas-add-faq-item').click();
    }

    await expect(page.getByTestId('sas-faq-row')).toHaveCount(3);

    await page.getByTestId('sas-faq-question').nth(0).fill('Question A');
    await setAnswer(page, 0, '<p>Answer A</p>');
    await page.getByTestId('sas-faq-position').nth(0).fill('20');

    await page.getByTestId('sas-faq-question').nth(1).fill('Question B');
    await setAnswer(page, 1, '<p>Answer B</p>');
    await page.getByTestId('sas-faq-position').nth(1).fill('10');

    await page.getByTestId('sas-faq-question').nth(2).fill('Disabled question');
    await setAnswer(page, 2, '<p>Disabled answer</p>');
    await page.getByTestId('sas-faq-position').nth(2).fill('5');
    await page.getByTestId('sas-faq-enabled').nth(2).uncheck();

    await savePost(page);
    await page.reload();

    await expect(page.getByTestId('sas-faq-row')).toHaveCount(3);
    await expect(page.getByTestId('sas-faq-question').nth(0)).toHaveValue('Question A');
    await expect(page.getByTestId('sas-faq-question').nth(1)).toHaveValue('Question B');
    await expect(page.getByTestId('sas-faq-enabled').nth(2)).not.toBeChecked();

    const faq = await getRestFaq(request);
    expect(faq).toHaveLength(2);
    expect(faq.map((item) => item.question)).toEqual(['Question B', 'Question A']);
    expect(faq.map((item) => item.position)).toEqual([10, 20]);
  });

  test('sanitizes stored question and answer markup without executing it', async ({ page, request }) => {
    await openEditor(page);
    await page.getByTestId('sas-faq-question').nth(0).fill('<img src=x onerror="window.__sasFaqExecuted=true">Safe question');
    await setAnswer(page, 0, '<p>Allowed <strong>answer</strong><script>window.__sasFaqExecuted=true</script></p>');
    await savePost(page);
    await page.reload();

    await expect(page.getByTestId('sas-faq-question').nth(0)).toHaveValue('Safe question');
    expect(await page.evaluate(() => window.__sasFaqExecuted)).toBeUndefined();

    const faq = await getRestFaq(request);
    const saved = faq.find((item) => item.question === 'Safe question');

    expect(saved.answer).toContain('<strong>answer</strong>');
    expect(saved.answer).not.toContain('<script');
  });

  test('removes every FAQ row from editor storage and REST output', async ({ page, request }) => {
    await openEditor(page);

    while ((await page.getByTestId('sas-faq-row').count()) > 0) {
      const removeButton = page.getByTestId('sas-remove-faq-row').first();

      if (!(await removeButton.isVisible())) {
        await page.getByTestId('sas-toggle-faq-row').first().press('Enter');
      }

      await removeButton.click();
    }

    await expect(page.getByTestId('sas-faq-row')).toHaveCount(0);
    await savePost(page);
    await page.reload();
    await expect(page.getByTestId('sas-faq-row')).toHaveCount(0);
    expect(await getRestFaq(request)).toEqual([]);
  });
});
