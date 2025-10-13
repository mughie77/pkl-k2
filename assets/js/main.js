// Custom JavaScript for PKL Digital App
// Author: Jules.ai

document.addEventListener('DOMContentLoaded', function () {
    console.log('PKL Digital App script loaded successfully.');

    // Sidebar Toggle Functionality
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        // Check local storage for sidebar state
        if (localStorage.getItem('sidebar-toggled') === 'true') {
            document.body.classList.add('sidebar-toggled');
        }

        sidebarToggle.addEventListener('click', function (event) {
            event.preventDefault();
            document.body.classList.toggle('sidebar-toggled');

            // Save state to local storage
            localStorage.setItem('sidebar-toggled', document.body.classList.contains('sidebar-toggled'));
        });
    }

    // Example: Add confirmation to delete buttons
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                event.preventDefault();
            }
        });
    });

});