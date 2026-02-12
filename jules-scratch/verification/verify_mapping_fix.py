
import re
from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch()
    context = browser.new_context()
    page = context.new_page()

    # Log in as admin
    page.goto("http://localhost:8084/login.php")
    page.get_by_placeholder("Username").fill("admin")
    page.get_by_placeholder("Password").fill("admin")
    page.get_by_role("button", name="Login").click()

    # Go to the mapping page
    page.goto("http://localhost:8084/internship_mapping.php")

    # Verify the header is displaying an academic year
    header = page.locator("h1.h3.mb-4.text-gray-800")
    expect(header).not_to_contain_text("Tahun Ajaran Belum Dipilih")

    page.screenshot(path="jules-scratch/verification/mapping_page_verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
