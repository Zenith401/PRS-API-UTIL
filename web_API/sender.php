<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Submission</title>
</head>
<body>
    <h2>Send an Email</h2>
    <form id="emailForm">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="subject">Subject:</label>
        <input type="text" id="subject" name="subject" required>

        <label for="body">Body:</label>
        <textarea id="body" name="body" rows="4" required></textarea>

        <button type="submit">Send Email</button>
    </form>

    <p id="response"></p>

    <script>
        document.getElementById('emailForm').addEventListener('submit', async function(event) {
            event.preventDefault();

            const email = document.getElementById('email').value;
            const subject = document.getElementById('subject').value;
            const body = document.getElementById('body').value;

            const requestData = {
                email: email,
                subject: subject,
                body: body
            };

            try {
                const response = await fetch('http://34.44.181.13/PRS-API-UTIL/web_API/email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });

                const result = await response.json();
                document.getElementById('response').textContent = result.message;
            } catch (error) {
                document.getElementById('response').textContent = "Error submitting email.";
            }
        });
    </script>
</body>
</html>
