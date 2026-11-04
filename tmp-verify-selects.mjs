import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

const base = 'http://127.0.0.1:8000';
const outDir = path.resolve('tmp-select-verify');
fs.mkdirSync(outDir, { recursive: true });

async function login(page, email, password) {
  await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
  await page.fill('#email', email);
  await page.fill('#password', password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.click('button[type="submit"]'),
  ]);
}

async function inspectSelect(page, label, screenshotName) {
  const select = page.locator('select').first();
  await select.waitFor({ state: 'attached', timeout: 15000 });

  // Choices replaces native select; open via the visible control
  const choices = page.locator('.choices').first();
  await choices.click();
  await page.waitForTimeout(300);

  const item = page.locator('.choices__list--dropdown .choices__item--choice').first();
  await item.waitFor({ state: 'visible', timeout: 5000 });

  const styles = await page.evaluate(() => {
    const list = document.querySelector('.choices__list--dropdown, .choices__list[aria-expanded]');
    const items = [...document.querySelectorAll('.choices__list--dropdown .choices__item--choice, .choices__list[aria-expanded] .choices__item--choice')];
    const inner = document.querySelector('.choices__inner');
    const single = document.querySelector('.choices__list--single .choices__item');
    const cs = (el) => el ? getComputedStyle(el) : null;
    const pack = (el) => {
      if (!el) return null;
      const s = cs(el);
      return {
        text: (el.textContent || '').trim().slice(0, 80),
        color: s.color,
        backgroundColor: s.backgroundColor,
        classes: el.className,
      };
    };
    return {
      bodyClass: document.body.className,
      list: pack(list),
      inner: pack(inner),
      single: pack(single),
      items: items.slice(0, 6).map(pack),
      cssVars: {
        optionBg: getComputedStyle(document.body).getPropertyValue('--pf-option-bg').trim(),
        optionText: getComputedStyle(document.body).getPropertyValue('--pf-option-text').trim(),
        controlBg: getComputedStyle(document.body).getPropertyValue('--pf-control-bg').trim(),
        controlText: getComputedStyle(document.body).getPropertyValue('--pf-control-text').trim(),
      },
    };
  });

  await page.screenshot({ path: path.join(outDir, screenshotName), fullPage: false });
  // close dropdown
  await page.keyboard.press('Escape');
  return { label, ...styles };
}

const browser = await chromium.launch({ headless: true });
const results = [];

try {
  // Admin Opportunity Intelligence learning-path dropdown
  {
    const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
    await login(page, 'admin@pathforge.test', 'password');
    await page.goto(`${base}/admin/opportunities`, { waitUntil: 'networkidle' });
    results.push(await inspectSelect(page, 'admin-learning-path', 'admin-learning-path.png'));
    await page.close();
  }

  // Organization role dropdown
  {
    const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
    await login(page, 'org@pathforge.test', 'password');
    await page.goto(`${base}/organization/members`, { waitUntil: 'networkidle' });
    results.push(await inspectSelect(page, 'org-role', 'org-role.png'));
    await page.close();
  }
} catch (err) {
  results.push({ error: String(err), stack: err.stack });
} finally {
  await browser.close();
}

fs.writeFileSync(path.join(outDir, 'results.json'), JSON.stringify(results, null, 2));
console.log(JSON.stringify(results, null, 2));
