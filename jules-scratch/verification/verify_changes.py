
import re
from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch()
    context = browser.new_context()
    page = context.new_page()

    # Log in as admin
    page.goto("http://localhost:8083/login.php")
    page.get_by_placeholder("Username").fill("admin")
    page.get_by_placeholder("Password").fill("admin")
    page.get_by_role("button", name="Login").click()

    # Check sidebar for removed link
    page.get_by_role("link", name="Pengaturan").click()
    expect(page.get_by_role("link", name="Hari Libur")).not_to_be_visible()
    page.screenshot(path="jules-scratch/verification/sidebar_verification.png")

    # Activate a different academic year
    page.goto("http://localhost:8083/manage_academic_years.php")
    # Find the first non-active year and click "Aktifkan"
    non_active_row = page.locator("tr:has(span.badge.bg-secondary)").first
    non_active_row.get_by_role("link", name="Aktifkan").click()

    # Verify the change on the manage_students page
    page.goto("http://localhost:8083/manage_students.php")

    # Get the name of the newly activated year from the success message
    flash_message = page.locator(".alert-success").inner_text()

    # Go back to the manage_academic_years page to find the name of the year that was just activated
    page.goto("http://localhost:8083/manage_academic_years.php")
    active_year_name = page.locator("tr:has(span.badge.bg-success)").locator("td").first.inner_text()

    # Go.goto("http://localhost:8083/manage_students.php")
    header_text = page.locator("h1.h3.mb-4.text-gray-800").inner_text()
    expect(page.locator("h1.h3.mb-4.text-gray-800")).to_contain_text(active_year_name)
    page.screenshot(path="jules-scratch/verification/academic_year_verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
