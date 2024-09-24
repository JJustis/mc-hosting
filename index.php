<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Free Minecraft Servers!</h1>
        </header>

        <section class="form-section">
            <h2>Create a New Server</h2>

            <?php
                // Generate a unique key for the security key field
                $generatedKey = bin2hex(random_bytes(16)); // Generates a 32-character hexadecimal key
            ?>

            <form id="serverForm">
                <input type="hidden" name="action" value="createServer">

                <label for="serverName">Server Name:</label>
                <input type="text" id="serverName" name="serverName" required>
                
				<label for="serverPort">betahut.bounceme.net:(Port (81-25560)):</label>
                <input type="text" id="serverPort" name="serverPort" required>
				
                <label for="maxPlayers">Max Players:</label>
                <input type="number" id="maxPlayers" name="maxPlayers" min="1" max="100" required>

                <label for="securityKey">Pre-generated Security Key (Required):</label>
                <input type="text" id="securityKey" name="securityKey" value="<?php echo htmlspecialchars($generatedKey); ?>" readonly required>

                <label for="pin">Enter PIN to Encrypt Key:</label>
                <input type="password" id="pin" name="pin" required>

                <!-- RCON Settings -->
                <label for="rconHost">RCON Host:</label>
                <input type="text" id="rconHost" name="rconHost" required>

                <label for="rconPort">RCON Port:</label>
                <input type="number" id="rconPort" name="rconPort" min="1" max="65535" required>

                <label for="rconPassword">RCON Password:</label>
                <input type="password" id="rconPassword" name="rconPassword" required>

                <button type="submit">Create Server (wait a 10 minutes and if it doesn't show then contact me @ betahut.bounceme.net/swiftpost)</button>
            </form>
        </section>

        <section class="servers-section">
            <h2>Existing Servers</h2>
            <div id="serversList" class="servers-list">
                <!-- Server cards with buttons leading to the control panel -->
            </div>
        </section>
    </div>
    
<script>
document.addEventListener("DOMContentLoaded", () => {
    const serverForm = document.getElementById('serverForm');
    const serversList = document.getElementById('serversList');

    // Function to fetch and display existing servers
    const fetchServers = async () => {
        try {
            const response = await fetch('servers.php');
            const data = await response.json();

            if (Array.isArray(data.servers)) {
                data.servers.forEach(server => addServerCard(server));
            } else {
                console.error('Invalid server data:', data);
            }
        } catch (error) {
            console.error('Error fetching servers:', error);
        }
    };

    // Function to add a server card to the list
    const addServerCard = (server) => {
        if (!server) {
            console.error('Server data is undefined');
            return;
        }

        const card = document.createElement('div');
        card.className = 'server-card';
        card.innerHTML = `
            <h3>${server.name}</h3>
            <p>Max Players: ${server.max_players}</p>
            <p>Status: ${server.status}</p>
            <button onclick="location.href='controlpanel.php?server=${encodeURIComponent(server.name)}'">Go to Control Panel</button>
        `;
        serversList.appendChild(card);
    };

    // Event listener for form submission
    serverForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(serverForm);

        try {
            const response = await fetch('functions.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                if (data.server) {
                    addServerCard(data.server);
                } else {
                    console.error('Server data missing in response');
                }
                alert(data.message);
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('Error submitting form:', error);
        }
    });

    // Initial fetch of servers
    fetchServers();
});
</script>
</body>
</html>
