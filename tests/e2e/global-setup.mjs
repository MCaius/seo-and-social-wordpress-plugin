import { execFileSync } from 'node:child_process';
import { mkdir } from 'node:fs/promises';
import { request } from '@playwright/test';

const baseURL = process.env.SAS_E2E_BASE_URL || 'http://localhost:8893';
const authDirectory = 'tests/e2e/.auth';

async function saveLoginState(username, password, outputFile) {
  const context = await request.newContext({ baseURL });

  await context.get('/wp-login.php');
  await context.post('/wp-login.php', {
    form: {
      log: username,
      pwd: password,
      'wp-submit': 'Log In',
      redirect_to: `${baseURL}/wp-admin/`,
      testcookie: '1',
    },
  });

  const state = await context.storageState();
  const authenticated = state.cookies.some((cookie) => cookie.name.startsWith('wordpress_logged_in_'));

  if (!authenticated) {
    await context.dispose();
    throw new Error(`Could not authenticate E2E user ${username}.`);
  }

  await context.storageState({ path: `${authDirectory}/${outputFile}` });
  await context.dispose();
}

export default async function globalSetup() {
  const npx = process.platform === 'win32' ? 'npx.cmd' : 'npx';

  execFileSync(
    npx,
    ['wp-env', '--config=.wp-env.e2e.json', 'run', 'cli', 'wp', 'eval-file', 'wp-content/qa-e2e/setup-fixtures.php'],
    { stdio: 'inherit' },
  );

  await mkdir(authDirectory, { recursive: true });
  await saveLoginState('admin', 'password', 'admin.json');
  await saveLoginState('sas-editor', 'password', 'editor.json');
  await saveLoginState('sas-author', 'password', 'author.json');
  await saveLoginState('sas-contributor', 'password', 'contributor.json');
  await saveLoginState('sas-subscriber', 'password', 'subscriber.json');
  await saveLoginState('sas-custom', 'password', 'custom.json');
}
