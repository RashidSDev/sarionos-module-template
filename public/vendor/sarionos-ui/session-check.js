window.addEventListener('load', function () {
    // Check session data by making an API request
    fetch('/check-session-data')
        .then(response => response.json())
        .then(data => {
            // If the API responds with a redirect URL, trigger the logout
            if (data.redirect) {
                // Redirect to the logout page
                window.location.href = data.redirect;
            }
        })
        .catch((error) => {
            console.error('Error checking session data:', error);
        });
});

