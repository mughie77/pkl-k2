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

    # Navigate to teacher_set_locations.php
    page.goto("http://localhost:8080/teacher_set_locations.php")

    # Open the "Set/Update Lokasi" modal
    page.click("button[data-bs-target='#locationModal']")

    # Wait for the modal to be visible
    page.wait_for_selector("#locationModal .modal-content")

    # Take a screenshot
    page.screenshot(path="jules-scratch/verification/teacher_set_locations.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
