import { expect, test } from '@playwright/test'

test('serves the Inertia bootstrap page', async ({ page }) => {
    await page.goto('/')

    await expect(page).toHaveTitle(/Bootstrap ready/)
    await expect(page.getByRole('heading', { name: /Identity Platform/i })).toBeVisible()
})

test('exposes healthy liveness and readiness probes', async ({ request }) => {
    await expect((await request.get('/health/live')).json()).resolves.toEqual({ status: 'ok' })
    await expect((await request.get('/health/ready')).json()).resolves.toEqual({ status: 'ready' })
})
