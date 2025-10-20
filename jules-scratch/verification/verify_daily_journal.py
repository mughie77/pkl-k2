from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Log in as student
    page.goto("http://localhost:8080/login.php")
    page.fill("input[name='username']", "0075696417")
    page.fill("input[name='password']", "admin")
    page.click("button[type='submit']")

    # Navigate to daily_journal.php
    page.goto("http://localhost:8080/daily_journal.php")

    # Take a screenshot
    page.screenshot(path="jules-scratch/verification/daily_journal.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
