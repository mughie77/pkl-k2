from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Log in as Waka Humas
    page.goto("http://localhost:8000/login.php")
    page.get_by_placeholder("Username").fill("wakahumas")
    page.get_by_placeholder("Password").fill("password")
    page.get_by_role("button", name="Log In").click()
    expect(page).to_have_url("http://localhost:8000/waka_humas_dashboard.php")

    # Navigate to the assessment report page
    page.goto("http://localhost:8000/assessment_report.php")

    # Verify the main components of the page are visible
    expect(page.get_by_role("heading", name="Laporan Penilaian PKL")).to_be_visible()
    expect(page.get_by_label("Program Keahlian")).to_be_visible()
    expect(page.get_by_label("Konsentrasi Keahlian")).to_be_visible()
    expect(page.get_by_label("Kelas")).to_be_visible()
    expect(page.get_by_role("button", name="Filter")).to_be_visible()

    # Check if the table with student data is present
    expect(page.locator("table.table-bordered")).to_be_visible()

    # Check for a specific student and the "Cetak Rapor" button
    # Assuming student 'Adit' is visible on the first page
    student_row = page.get_by_role("row", name="Adit").first
    if student_row.is_visible():
        expect(student_row.get_by_text("Adit")).to_be_visible()
        # Find the "Cetak Rapor" button within that student's row
        cetak_button = student_row.get_by_role("link", name="Cetak Rapor")
        expect(cetak_button).to_be_visible()
        expect(cetak_button).to_have_attribute("href")

    # Take a screenshot
    page.screenshot(path="jules-scratch/verification/assessment_report.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)