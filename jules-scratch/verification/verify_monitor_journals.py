from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Log in as teacher
    page.goto("http://localhost:8080/login.php")
    page.fill("input[name='username']", "196509261999032002")
    page.fill("input[name='password']", "admin")
    page.click("button[type='submit']")

    # Navigate to monitor_journals.php
    page.goto("http://localhost:8080/monitor_journals.php")

    # Open the "Lihat Lokasi" modal
    page.click("button[data-bs-target='#viewLocationModal']")

    # Wait for the modal to be visible
    page.wait_for_selector("#viewLocationModal .modal-content")

    # Take a screenshot
    page.screenshot(path="jules-scratch/verification/monitor_journals.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
