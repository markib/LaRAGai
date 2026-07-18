const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

if (token) {
    // Set up CSRF token for any future AJAX requests
    window.csrfToken = token;
}

