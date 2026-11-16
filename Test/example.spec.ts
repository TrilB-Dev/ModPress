import { test, expect } from '@playwright/test';

async function loginAsAdmin(page: any) {
  await page.goto('/wp-login.php');
  await page.getByLabel('Username or Email Address').fill('admin');
  await page.locator('#user_pass').fill('password');
  await page.getByRole('button', { name: 'Log In' }).click();
  await expect(page).toHaveURL(/\/wp-admin\//);
}

test('renders the ModPress admin dashboard', async ({ page }) => {
  await loginAsAdmin(page);

  await page.goto('/wp-admin/admin.php?page=modpress');

  await expect(page.getByRole('heading', { name: 'Quick actions', exact: true })).toBeVisible();
  await expect(page.locator('a.wikipress-summary-card')).toHaveCount(6);
  await expect(page.getByRole('link', { name: /User groups/i })).toBeVisible();
  await expect(page.getByRole('link', { name: /Access settings/i })).toBeVisible();
});

test('loads the Mods manager page and shows the Mods list screen', async ({ page }) => {
  await loginAsAdmin(page);

  await page.goto('/wp-admin/admin.php?page=modpress&group=mods-manager&tab=mods');

  await expect(page.getByRole('heading', { name: 'Mods Manager', exact: true })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Mods', exact: true })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Create New Mod', exact: true }).first()).toBeVisible();
});
