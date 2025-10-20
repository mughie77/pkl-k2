from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Log in as admin
    page.goto("http://localhost:8080/login.php")
    page.fill("input[name='username']", "admin")
    page.fill("input[name='password']", "admin")
    page.click("button[type='submit']")

    # Navigate to manage_companies.php
    page.goto("http://localhost:8080/manage_companies.php")

    # Open the "Tambah DUDIKA Baru" modal
    page.click("button[data-bs-target='#companyModal']")

    # Wait for the modal to be visible
    page.wait_for_selector("#companyModal .modal-content")

    # Take a screenshot
    page.screenshot(path="jules-scratch/verification/manage_companies.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
