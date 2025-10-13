// Custom JavaScript for PKL Digital App
// Author: Jules.ai

document.addEventListener('DOMContentLoaded', function () {
    console.log('PKL Digital App script loaded successfully.');

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