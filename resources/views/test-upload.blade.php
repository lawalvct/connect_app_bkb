<!DOCTYPE html>
<html>
<head>
    <title>Test Profile Upload</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>Test Profile Upload</h1>

    <form action="/test-upload" method="POST" enctype="multipart/form-data">
        @csrf
        <div>
            <label for="profile_image">Choose Profile Image:</label>
            <input type="file" name="profile_image" id="profile_image" accept="image/*" required>
        </div>
        <br>
        <button type="submit">Upload Test File</button>
    </form>

    <hr>

    <h2>Test Step 5 Registration</h2>
    <form action="/api/v1/auth/register-step5" method="POST" enctype="multipart/form-data">
        @csrf
        <div>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" placeholder="Enter user email" required>
        </div>
        <br>
        <div>
            <label for="bio">Bio:</label>
            <textarea name="bio" id="bio" placeholder="Enter bio"></textarea>
        </div>
        <br>
        <div>
            <label for="profile_image_step5">Profile Image:</label>
            <input type="file" name="profile_image" id="profile_image_step5" accept="image/*">
        </div>
        <br>
        <button type="submit">Submit Step 5</button>
    </form>

    <script>
        // Handle AJAX for step 5 test
        document.querySelector('form[action="/api/v1/auth/register-step5"]').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('/api/v1/auth/register-step5', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('Step 5 Response:', data);
                alert('Response: ' + JSON.stringify(data, null, 2));
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            });
        });
    </script>
</body>
</html>
