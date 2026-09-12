// Headless axe audit of the widget on the real dashboard, as run for Phase 3.3.
// Not a turnkey tool: it imports Playwright and @axe-core/playwright from a
// sibling project, expects the dev site at http://statamic-dev.test with the
// demo contributors provider, and needs a temporary local-only route
// /_site-weather-audit-login that logs the first user in and redirects to
// /cp/dashboard (add it for the run, remove it after). Results for the run
// that produced docs/screenshots are in axe-2026-09-11.json.
import { chromium } from '/Users/bpmore/Herd/windrow/node_modules/playwright/index.mjs';
import { AxeBuilder } from '/Users/bpmore/Herd/windrow/node_modules/@axe-core/playwright/dist/index.mjs';
import { writeFileSync } from 'node:fs';

const OUT = process.argv[2];
const BASE = 'http://statamic-dev.test';
const results = {};
const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1280, height: 800 }, reducedMotion: 'reduce' });
const page = await context.newPage();
const console_ = [];
page.on('console', m => { if (['error', 'warning'].includes(m.type())) console_.push(`${m.type()}: ${m.text()}`); });
page.on('pageerror', e => console_.push(`pageerror: ${e.message}`));

async function audit(name, url, scheme) {
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.evaluate(s => document.documentElement.classList.toggle('dark', s === 'dark'), scheme);
  await page.waitForSelector('.site-weather', { timeout: 15000 });
  await page.waitForTimeout(300);
  const widget = page.locator('.site-weather').first();
  const card = widget.locator('xpath=ancestor::*[contains(@class,"rounded-xl")][1]');
  await card.screenshot({ path: `${OUT}/${name}-${scheme}.png` });
  // axe on the widget card, then on the whole page filtered to nodes inside the widget.
  const axeWidget = await new AxeBuilder({ page }).include('.site-weather').withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice']).analyze();
  const axePage = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']).analyze();
  const inWidget = v => ({ id: v.id, impact: v.impact, help: v.help, nodes: v.nodes.filter(n => n.target.some(t => String(t).includes('site-weather'))).map(n => ({ target: n.target, summary: n.failureSummary })) });
  results[`${name}-${scheme}`] = {
    violations: axeWidget.violations.map(v => ({ id: v.id, impact: v.impact, help: v.help, nodes: v.nodes.map(n => ({ target: n.target, summary: n.failureSummary })) })),
    incomplete: axeWidget.incomplete.map(v => ({ id: v.id, help: v.help, nodes: v.nodes.length })),
    passes: axeWidget.passes.map(v => v.id),
    pageViolationsTouchingWidget: axePage.violations.map(inWidget).filter(v => v.nodes.length),
    pageViolationsTotal: axePage.violations.length,
    text: (await widget.innerText()).trim(),
    srSummary: await widget.locator('.sr-only').first().textContent().catch(() => null),
    animated: await page.evaluate(() => Array.from(document.querySelectorAll('.site-weather, .site-weather *')).some(el => { const s = getComputedStyle(el); return (s.animationName && s.animationName !== 'none') || (s.transitionDuration && s.transitionDuration !== '0s'); })),
  };
}

await page.goto(`${BASE}/_site-weather-audit-login`, { waitUntil: 'networkidle' });
for (const scheme of ['light', 'dark']) {
  await audit('populated', `${BASE}/cp/dashboard`, scheme);
  await audit('empty', `${BASE}/cp/dashboard?weather-empty=1`, scheme);
}

// Keyboard: Tab until focus lands inside the widget, then read the computed outline and screenshot.
await page.goto(`${BASE}/cp/dashboard`, { waitUntil: 'networkidle' });
await page.evaluate(() => document.documentElement.classList.remove('dark'));
await page.waitForSelector('.site-weather-bands a');
let focused = null;
for (let i = 0; i < 60; i++) {
  await page.keyboard.press('Tab');
  focused = await page.evaluate(() => {
    const el = document.activeElement;
    if (!el || !el.closest('.site-weather')) return null;
    const s = getComputedStyle(el);
    return { text: el.textContent.trim(), tag: el.tagName, focusVisible: el.matches(':focus-visible'), outlineStyle: s.outlineStyle, outlineWidth: s.outlineWidth, outlineColor: s.outlineColor, outlineOffset: s.outlineOffset, textDecoration: s.textDecorationLine };
  });
  if (focused) break;
}
results.focus = focused;
if (focused) {
  const card = page.locator('.site-weather').first().locator('xpath=ancestor::*[contains(@class,"rounded-xl")][1]');
  await card.screenshot({ path: `${OUT}/focus-light.png` });
}
results.console = console_;
writeFileSync(`${OUT}/results.json`, JSON.stringify(results, null, 2));
await browser.close();
console.log(JSON.stringify(results, null, 2));
