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
      await expect(async () => {
        if ((await metaBoxesAreaToggle.getAttribute('aria-expanded')) !== 'true') {
          await metaBoxesAreaToggle.press('Enter');
        }

        await expect(metaBoxesAreaToggle).toHaveAttribute('aria-expanded', 'true');
      }).toPass();
    }

    if (!(await metaBox.isVisible())) {
      const faqMetaBoxToggle = page.getByTestId('sas-toggle-faq-meta-box');

      await expect(faqMetaBoxToggle).toBeVisible();
      await faqMetaBoxToggle.press('Enter');
    }

    await expect(metaBox).toBeVisible();

    const firstQuestion = page.getByTestId('sas-faq-question').first();

    if ((await firstQuestion.count()) > 0 && !(await firstQuestion.isVisible())) {
      await page.getByTestId('sas-toggle-faq-row').first().press('Enter');
      await expect(firstQuestion).toBeVisible();
    }
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
    const textarea = page.getByTestId('sas-faq-answer').nth(index);

    await expect.poll(() => textarea.evaluate((element) => (
      element.dataset.editorInitialized === 'true'
        && Boolean(window.tinyMCE?.get(element.id)?.initialized)
    ))).toBe(true);

    await textarea.evaluate((element, answer) => {
      const editor = window.tinyMCE?.get(element.id);

      element.value = answer;
      element.dispatchEvent(new Event('input', { bubbles: true }));
      element.dispatchEvent(new Event('change', { bubbles: true }));

      if (editor) {
        editor.setContent(answer);
        editor.save();
      }
    }, value);

    await expect(textarea).toHaveValue(value);
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

    const editorIds = await page.getByTestId('sas-faq-answer').evaluateAll(
      (editors) => editors.map((editor) => editor.id),
    );

    expect(new Set(editorIds).size).toBe(3);
    expect(editorIds.every((id) => !id.includes('__INDEX__') && !id.includes('__index__'))).toBe(true);

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

    await expect(page.getByTestId('sas-faq-answer').nth(0)).toHaveValue('<p>Answer A</p>');
    await expect(page.getByTestId('sas-faq-answer').nth(1)).toHaveValue('<p>Answer B</p>');
    await expect(page.getByTestId('sas-faq-answer').nth(2)).toHaveValue('<p>Disabled answer</p>');

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

  test('keeps Code editor toolbar buttons compact in existing and added rows', async ({ page }) => {
    await openEditor(page);
    await page.getByTestId('sas-add-faq-item').click();

    const editorIndexes = [0, (await page.getByTestId('sas-faq-answer').count()) - 1];

    for (const [toolbarIndex, index] of editorIndexes.entries()) {
      const row = page.getByTestId('sas-faq-row').nth(index);
	  const codeTab = page.getByTestId('sas-faq-code-tab').nth(toolbarIndex);

	  if (!(await codeTab.isVisible())) {
        await row.getByTestId('sas-toggle-faq-row').press('Enter');
      }

	  await expect(codeTab).toBeVisible();
	  await codeTab.click();

	  const toolbar = page.getByTestId('sas-faq-code-toolbar').nth(toolbarIndex);
	  const buttons = toolbar.getByTestId('sas-faq-code-button');

      await expect(toolbar).toBeVisible();
      expect(await buttons.count()).toBeGreaterThan(1);
	  expect(await toolbar.evaluate((element) => {
		const toolbarWidth = element.getBoundingClientRect().width;
		return Array.from(element.children)
		  .filter((item) => item.tagName === 'INPUT')
		  .every((item) => item.getBoundingClientRect().width < toolbarWidth / 2);
      })).toBe(true);
    }
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
