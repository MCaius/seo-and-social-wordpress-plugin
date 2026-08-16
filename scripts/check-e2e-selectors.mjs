import { readFileSync, readdirSync } from 'node:fs';
import { resolve } from 'node:path';

const testDirectory = resolve('tests/e2e');
const prohibitedSelectors = [
  '.locator(',
  '.getByRole(',
  '.getByText(',
  '.getByLabel(',
  '.getByPlaceholder(',
  '.getByAltText(',
  '.getByTitle(',
  '.querySelector(',
  '.querySelectorAll(',
];

const specFiles = readdirSync(testDirectory)
  .filter((file) => file.endsWith('.spec.mjs'))
  .map((file) => resolve(testDirectory, file));

const violations = [];

for (const file of specFiles) {
  const source = readFileSync(file, 'utf8');

  for (const selector of prohibitedSelectors) {
    if (source.includes(selector)) {
      violations.push(`${file}: prohibited selector ${selector}`);
    }
  }
}

if (violations.length) {
  console.error('E2E tests must use getByTestId() for plugin-owned elements.');
  console.error(violations.join('\n'));
  process.exit(1);
}

console.log(`Verified ${specFiles.length} E2E spec file(s): getByTestId() selector policy passed.`);
