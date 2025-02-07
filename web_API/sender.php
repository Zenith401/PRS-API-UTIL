<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Submission</title>
</head>
<body>
    <h2>Enter Your Email</h2>
    <form id="emailForm">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <button type="submit">Submit</button>
    </form>

    <p id="response"></p>

    <script>
        document.getElementById('emailForm').addEventListener('submit', async function(event) {
            event.preventDefault();

            const email = document.getElementById('email').value;
            const formData = new FormData();
            formData.append('email', email);

            try {
                const response = await fetch('http://34.44.181.13/PRS-API-UTIL/web_API/email.php', { // Replace with actual PHP file path
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                document.getElementById('success').textContent = result.success || result.error;
            } catch (error) {
                document.getElementById('error').textContent = "Error submitting email.";
            }
        });
    </script>
</body>
</html>