from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    try:
        # Go to the login page
        page.goto("http://localhost:8080/login.php")

        # Fill in the credentials for the admin user
        page.get_by_placeholder("Masukkan username Anda").fill("admin")
        page.get_by_placeholder("********").fill("admin")

        # Click the login button
        page.get_by_role("button", name="Login").click()

        # Wait for the dashboard to load and verify the title
        expect(page).to_have_title("Dashboard Admin - PKL Digital")

        # Verify that the welcome message is for the admin
        expect(page.get_by_text("Selamat Datang, Admin!")).to_be_visible()

        # Take a screenshot of the admin dashboard
        page.screenshot(path="jules-scratch/verification/admin_dashboard.png")

        print("Verification successful. Screenshot saved to jules-scratch/verification/admin_dashboard.png")

    except Exception as e:
        print(f"An error occurred: {e}")
        page.screenshot(path="jules-scratch/verification/error.png")

    finally:
        browser.close()

with sync_playwright() as playwright:
    run(playwright)