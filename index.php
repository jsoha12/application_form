<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Application</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            height: 100vh;
            display: grid;
            place-items: center;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="text"], input[type="date"], input[type="file"], input[type="checkbox"], select {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="checkbox"] {
            width: auto;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .signature-pad {
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div id="application-form">
        <h1>Job Application Form</h1>
        <form id="jobApplicationForm" enctype="multipart/form-data">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required><br>
            <label for="age">Age:</label>
            <input type="text" id="age" name="age" required><br>
            <label for="birthday">Birthday:</label>
            <input type="date" id="birthday" name="birthday" required><br>
            <label for="address">Address:</label>
            <input type="text" id="address" name="address" required><br>
            <label for="position">Position:</label>
            <select id="position" name="position" required>
                <option value="" disabled selected>Select a position</option>
                <option value="IT Technician">IT Technician</option>
                <option value="Web Developer">Web Developer</option>
                <option value="IT Security Specialist">IT Security Specialist</option>
                <option value="Programmer">Programmer</option>
                <option value="Systems Analyst">Systems Analyst</option>
            </select><br>
            <label for="id_picture">ID Picture:</label>
            <input type="file" id="id_picture" name="id_picture" required><br>
            <label for="resume">Resume:</label>
            <input type="file" id="resume" name="resume" required><br>
            <label for="signature">Signature:</label>
            <canvas id="signature-pad" class="signature-pad" width=400 height=200></canvas><br>
            <label>
                <input type="checkbox" id="agreement" name="agreement" required>
                I agree to the collection and processing of my data.
            </label><br>
            <button type="button" id="clear">Clear</button>
            <button type="submit">Apply</button>
        </form>
    </div>
    <div id="confirmation" class="hidden">
        <h1>Application Confirmation</h1>
        <p>Thank you for applying!</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
        // Signature pad setup
        var canvas = document.getElementById('signature-pad');
        var signaturePad = new SignaturePad(canvas);

        document.getElementById('clear').addEventListener('click', function() {
            signaturePad.clear();
        });

        // Generate RSA keys
        async function generateKeys() {
            const keyPair = await window.crypto.subtle.generateKey(
                {
                    name: "RSA-PSS",
                    modulusLength: 2048,
                    publicExponent: new Uint8Array([0x01, 0x00, 0x01]),
                    hash: "SHA-256"
                },
                true,
                ["sign", "verify"]
            );

            const privateKey = await window.crypto.subtle.exportKey(
                "pkcs8",
                keyPair.privateKey
            );
            const publicKey = await window.crypto.subtle.exportKey(
                "spki",
                keyPair.publicKey
            );

            return { privateKey, publicKey, keyPair };
        }

        // Sign data
        async function signData(privateKey, data) {
            const signature = await window.crypto.subtle.sign(
                {
                    name: "RSA-PSS",
                    saltLength: 32
                },
                privateKey,
                data
            );

            return signature;
        }

        document.getElementById('jobApplicationForm').addEventListener('submit', async function(event) {
            event.preventDefault();

            if (signaturePad.isEmpty()) {
                alert("Please provide a signature.");
                return;
            }

            if (!document.getElementById('agreement').checked) {
                alert("You must agree to the collection and processing of your data.");
                return;
            }

            const formData = new FormData(this);
            const signatureData = signaturePad.toDataURL();

            formData.append('signature', signatureData);

            // Generate keys
            const { privateKey, publicKey, keyPair } = await generateKeys();

            // Sign form data
            const encoder = new TextEncoder();
            const dataToSign = encoder.encode([...formData.entries()].map(e => e.join('=')).join('&'));
            const signature = await signData(keyPair.privateKey, dataToSign);

            formData.append('public_key', JSON.stringify(Array.from(new Uint8Array(publicKey))));
            formData.append('private_key', JSON.stringify(Array.from(new Uint8Array(privateKey))));
            formData.append('rsa_signature', JSON.stringify(Array.from(new Uint8Array(signature))));

            // Post form data to server
            fetch('api/apply.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
            if (data.message == 'Application submitted successfully.') {
                document.getElementById('application-form').classList.add('hidden');
                document.getElementById('confirmation').classList.remove('hidden');
            }
            })
                .catch(error => {
                    console.error('Error:', error);
            });

        });
    </script>
</body>
</html>