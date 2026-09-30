import { execFileSync } from 'node:child_process';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from '@playwright/test';

const baseURL = process.env.SAS_E2E_BASE_URL || 'http://localhost:8893';
const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const imageDirectory = path.join(projectRoot, 'docs', 'images');

const seedCode = String.raw`
$settings = sas_get_default_settings();
$settings['social'] = array(
	'email' => 'hello@example.com',
	'phone_whatsapp' => '+1 202 555 0147',
	'facebook_url' => 'https://facebook.com/example',
	'instagram_url' => 'https://instagram.com/example',
	'tiktok_url' => 'https://tiktok.com/@example',
	'youtube_url' => 'https://youtube.com/@example',
	'extra_links' => array(
		array(
			'key' => 'linkedin',
			'label' => 'LinkedIn',
			'url' => 'https://linkedin.com/company/example',
		),
	),
);
$settings['seo'] = array_merge(
	$settings['seo'],
	array(
		'site_name' => 'Example Studio',
		'default_meta_title' => 'Example Studio — Thoughtful digital experiences',
		'default_meta_description' => 'A concise global description used when a page does not provide its own SEO summary.',
		'schema_type' => 'Organization',
		'organization_name' => 'Example Studio',
		'website_url' => 'https://example.com',
		'address' => '123 Example Street',
		'city' => 'Bucharest',
		'country' => 'Romania',
		'postal_code' => '010101',
		'opening_hours' => 'Mo-Fr 09:00-18:00',
		'price_range' => '$$',
		'extra_schema_properties' => array(
			array(
				'key' => 'sameAs',
				'type' => 'url',
				'value' => 'https://www.linkedin.com/company/example',
			),
		),
	)
);
update_option( 'sas_settings', $settings, false );

$post = get_page_by_path( 'sas-e2e-seo', OBJECT, 'page' );

if ( ! $post ) {
	WP_CLI::error( 'The sas-e2e-seo fixture page is missing.' );
}

update_post_meta(
	$post->ID,
	'_sas_seo_overrides',
	array(
		'seo_title' => 'A clear, page-specific SEO title',
		'seo_description' => 'A concise description that overrides the global SEO default for this page.',
		'og_image_id' => 0,
		'og_image_url' => '',
		'canonical_url' => 'https://example.com/services/',
		'robots' => 'index,follow',
		'schema_type' => 'WebPage',
		'custom_schema_json' => '',
	)
);
update_post_meta(
	$post->ID,
	'_sas_faq_items',
	array(
		array(
			'question' => 'What does this service include?',
			'answer' => '<p>Strategy, implementation, and ongoing support.</p>',
			'enabled' => true,
			'position' => 10,
		),
		array(
			'question' => 'How quickly can we get started?',
			'answer' => '<p>Most projects can begin within two weeks.</p>',
			'enabled' => true,
			'position' => 20,
		),
	)
);
`;

function runWp(...args) {
  const npx = process.platform === 'win32' ? 'npx.cmd' : 'npx';

  execFileSync(
    npx,
    ['wp-env', '--config=.wp-env.e2e.json', 'run', 'cli', 'wp', ...args],
    { cwd: projectRoot, stdio: 'inherit' },
  );
}

async function fetchJson(route) {
  const response = await fetch(`${baseURL}${route}`);

  if (!response.ok) {
    throw new Error(`REST request failed (${response.status}): ${route}`);
  }

  return response.json();
}

function pick(object, keys) {
  return Object.fromEntries(keys.map((key) => [key, object[key]]));
}

function escapeHtml(value) {
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;');
}

function highlightJson(value) {
  const escaped = escapeHtml(JSON.stringify(value, null, 2));

  return escaped.replace(
    /("(?:\\u[a-fA-F0-9]{4}|\\[^u]|[^\\"])*")(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?/g,
    (match, string, keySuffix, literal) => {
      if (string) {
        const className = keySuffix ? 'json-key' : 'json-string';
        return `<span class="${className}">${string}</span>${keySuffix || ''}`;
      }

      return `<span class="${literal ? 'json-literal' : 'json-number'}">${match}</span>`;
    },
  );
}

function pngDimensions(buffer) {
  const signature = buffer.subarray(1, 4).toString('ascii');

  if (signature !== 'PNG') {
    throw new Error('README composition inputs must be PNG files.');
  }

  return {
    width: buffer.readUInt32BE(16),
    height: buffer.readUInt32BE(20),
  };
}

function renderArrow(arrow, imageX, imageY, imageWidth, panelX, panelY, markerId) {
  const startX = imageX + imageWidth - 10;
  const startY = imageY + arrow.fromY;
  const endX = panelX + 8;
  const endY = panelY + arrow.toY;
  const centerX = (startX + endX) / 2;
  const centerY = (startY + endY) / 2;
  const curve = Math.max(24, (endX - startX) * 0.42);
  const labelWidth = Math.max(88, arrow.label.length * 9 + 24);

  return `
    <path class="callout-path" d="M ${startX} ${startY} C ${startX + curve} ${startY}, ${endX - curve} ${endY}, ${endX} ${endY}" marker-end="url(#${markerId})" />
    <g transform="translate(${centerX - labelWidth / 2} ${centerY - 15})">
      <rect class="callout-label-bg" width="${labelWidth}" height="30" rx="15" />
      <text class="callout-label" x="${labelWidth / 2}" y="20" text-anchor="middle">${escapeHtml(arrow.label)}</text>
    </g>
  `;
}

async function renderComposite(browser, config) {
  const inputPath = path.join(imageDirectory, config.input);
  const input = await readFile(inputPath);
  const dimensions = pngDimensions(input);
  const imageX = 32;
  const headerHeight = 76;
  const columnHeaderHeight = 42;
  const imageY = headerHeight + columnHeaderHeight;
  const gap = 132;
  const panelWidth = 1000;
  const panelX = imageX + dimensions.width + gap;
  const panelY = imageY;
  const canvasWidth = panelX + panelWidth + 32;
  const canvasHeight = imageY + dimensions.height + 32;
  const markerId = `arrow-${config.slug}`;
  const arrows = config.arrows
    .map((arrow) => renderArrow(arrow, imageX, imageY, dimensions.width, panelX, panelY, markerId))
    .join('');
  const imageUrl = `data:image/png;base64,${input.toString('base64')}`;
  const sections = config.sections || [
    {
      title: 'JSON response',
      note: 'Irrelevant WordPress fields omitted',
      y: 0,
      height: dimensions.height,
      json: config.json,
    },
  ];
  const panels = sections.map((section) => `
    <section class="api-panel" style="top: ${panelY + section.y}px; height: ${section.height}px;">
      <div class="api-panel-header">
        <span class="api-panel-title">${escapeHtml(section.title)}</span>
        <span class="api-panel-note">${escapeHtml(section.note || 'From actual response')}</span>
      </div>
      <pre>${highlightJson(section.json)}</pre>
    </section>
  `).join('');

  const html = `<!doctype html>
    <html>
      <head>
        <meta charset="utf-8">
        <style>
          * { box-sizing: border-box; }
          html, body { margin: 0; background: #eef0f2; }
          body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #1d2327; }
          #composition { position: relative; width: ${canvasWidth}px; height: ${canvasHeight}px; overflow: hidden; background: #eef0f2; }
          .title { position: absolute; top: 24px; left: 32px; font-size: 30px; line-height: 38px; font-weight: 700; letter-spacing: -0.02em; }
          .endpoint { position: absolute; top: 24px; right: 32px; padding: 9px 15px; border: 1px solid #c3c4c7; border-radius: 8px; background: #fff; color: #3c434a; font: 600 17px/22px ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
          .column-label { position: absolute; top: ${headerHeight + 8}px; color: #50575e; font-size: 17px; line-height: 24px; font-weight: 650; text-transform: uppercase; letter-spacing: 0.06em; }
          .ui-label { left: ${imageX}px; }
          .api-label { left: ${panelX}px; }
          .ui-image { position: absolute; top: ${imageY}px; left: ${imageX}px; display: block; width: ${dimensions.width}px; height: ${dimensions.height}px; border: 1px solid #dcdcde; border-radius: 10px; box-shadow: 0 2px 8px rgba(29, 35, 39, 0.08); }
          .api-panel { position: absolute; left: ${panelX}px; width: ${panelWidth}px; overflow: hidden; border: 1px solid #30363d; border-radius: 12px; background: #0d1117; box-shadow: 0 4px 14px rgba(29, 35, 39, 0.14); color: #e6edf3; }
          .api-panel-header { display: flex; align-items: center; justify-content: space-between; height: 58px; padding: 0 24px; border-bottom: 1px solid #30363d; background: #161b22; }
          .api-panel-title { font-size: 18px; font-weight: 700; }
          .api-panel-note { color: #8b949e; font-size: 15px; }
          pre { margin: 0; padding: 24px 26px 30px; white-space: pre-wrap; overflow-wrap: anywhere; color: #c9d1d9; font: ${config.fontSize}px/${config.lineHeight} ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; tab-size: 2; }
          .json-key { color: #79c0ff; }
          .json-string { color: #a5d6ff; }
          .json-number { color: #d2a8ff; }
          .json-literal { color: #ff7b72; }
          .callouts { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; overflow: visible; }
          .callout-path { fill: none; stroke: #2271b1; stroke-width: 3; stroke-linecap: round; }
          .callout-label-bg { fill: #fff; stroke: #72aee6; stroke-width: 1.5; }
          .callout-label { fill: #135e96; font-size: 14px; font-weight: 700; letter-spacing: 0.01em; }
        </style>
      </head>
      <body>
        <main id="composition">
          <div class="title">${escapeHtml(config.title)}</div>
          <div class="endpoint">GET ${escapeHtml(config.endpoint)}</div>
          <div class="column-label ui-label">WordPress Admin</div>
          <div class="column-label api-label">REST API · focused response</div>
          <img class="ui-image" src="${imageUrl}" alt="">
          ${panels}
          <svg class="callouts" viewBox="0 0 ${canvasWidth} ${canvasHeight}" aria-hidden="true">
            <defs>
              <marker id="${markerId}" markerWidth="9" markerHeight="9" refX="8" refY="4.5" orient="auto">
                <path d="M 0 0 L 9 4.5 L 0 9 z" fill="#2271b1"></path>
              </marker>
            </defs>
            ${arrows}
          </svg>
        </main>
      </body>
    </html>`;

  const page = await browser.newPage({ viewport: { width: canvasWidth, height: canvasHeight } });

  try {
    await page.setContent(html, { waitUntil: 'load' });
    await page.locator('#composition').screenshot({
      animations: 'disabled',
      path: path.join(imageDirectory, config.output),
    });
  } finally {
    await page.close();
  }
}

async function main() {
  runWp('eval-file', 'wp-content/qa-e2e/setup-fixtures.php');
  runWp('eval', seedCode);

  const siteSettings = await fetchJson('/wp-json/headless-seo/v1/site-settings');
  const fixturePages = await fetchJson('/wp-json/wp/v2/pages?slug=sas-e2e-seo&_fields=id');

  if (fixturePages.length !== 1) {
    throw new Error('Could not resolve the deterministic sas-e2e-seo fixture page.');
  }

  const content = await fetchJson(
    `/wp-json/wp/v2/pages/${fixturePages[0].id}?_fields=seo_overrides,seo_resolved,faq_items`,
  );
  const overrideKeys = [
    'seo_title',
    'seo_description',
    'canonical_url',
    'robots',
    'schema_type',
  ];
  const resolvedKeys = [...overrideKeys, 'source'];
  const browser = await chromium.launch();

  try {
    await renderComposite(browser, {
      slug: 'social',
      title: 'Social settings → REST API',
      endpoint: '/wp-json/headless-seo/v1/site-settings',
      input: 'social-settings.png',
      output: 'social-settings-api.png',
      json: { social: siteSettings.social },
      fontSize: 22,
      lineHeight: 1.55,
      arrows: [
        { fromY: 365, toY: 275, label: 'contact + profiles' },
        { fromY: 760, toY: 675, label: 'extra link' },
      ],
    });

    await renderComposite(browser, {
      slug: 'seo',
      title: 'Global SEO settings → REST API',
      endpoint: '/wp-json/headless-seo/v1/site-settings',
      input: 'global-seo-settings.png',
      output: 'global-seo-settings-api.png',
      fontSize: 21,
      lineHeight: 1.52,
      sections: [
        {
          title: 'SEO defaults',
          y: 0,
          height: 565,
          json: {
            seo: pick(siteSettings.seo, [
              'site_name',
              'default_meta_title',
              'default_meta_description',
              'default_robots',
            ]),
          },
        },
        {
          title: 'Organization schema data',
          y: 650,
          height: 850,
          json: {
            seo: pick(siteSettings.seo, [
              'schema_type',
              'organization_name',
              'website_url',
              'address',
              'city',
              'country',
              'postal_code',
              'opening_hours',
              'price_range',
            ]),
          },
        },
        {
          title: 'Extra schema properties',
          y: 1885,
          height: 420,
          json: {
            seo: pick(siteSettings.seo, ['extra_schema_properties']),
          },
        },
      ],
      arrows: [
        { fromY: 360, toY: 285, label: 'SEO defaults' },
        { fromY: 1120, toY: 990, label: 'schema data' },
        { fromY: 2150, toY: 2080, label: 'extra property' },
      ],
    });

    await renderComposite(browser, {
      slug: 'content',
      title: 'Page-specific SEO & FAQ → REST API',
      endpoint: '/wp-json/wp/v2/pages/{id}',
      input: 'page-specific-seo-faq.png',
      output: 'page-specific-seo-faq-api.png',
      fontSize: 19,
      lineHeight: 1.42,
      sections: [
        {
          title: 'faq_items',
          y: 0,
          height: 655,
          json: { faq_items: content.faq_items },
        },
        {
          title: 'seo_overrides',
          y: 700,
          height: 410,
          json: { seo_overrides: pick(content.seo_overrides, overrideKeys) },
        },
        {
          title: 'seo_resolved',
          y: 1150,
          height: 570,
          json: { seo_resolved: pick(content.seo_resolved, resolvedKeys) },
        },
      ],
      arrows: [
        { fromY: 420, toY: 330, label: 'FAQ items' },
        { fromY: 1110, toY: 900, label: 'overrides' },
        { fromY: 1450, toY: 1430, label: 'resolved SEO' },
      ],
    });
  } finally {
    await browser.close();
  }
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
