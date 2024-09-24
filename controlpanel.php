<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$serverName = $_GET['server'] ?? '';

// Function to sanitize input
function sanitize_input($input) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
}

$serverName = sanitize_input($serverName);

// Function to load servers data
function load_servers_data() {
    $json_file = 'servers_data.json';
    if (file_exists($json_file)) {
        return json_decode(file_get_contents($json_file), true);
    }
    return [];
}

$servers_data = load_servers_data();

if (!isset($servers_data[$serverName])) {
    die("Server not found");
}

$server = $servers_data[$serverName];

// Handle PIN submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'] ?? '';

    // Decrypt the security key
    $decryptedKey = openssl_decrypt($server['security_key'], 'AES-128-ECB', $pin);

    if ($decryptedKey) {
        $_SESSION['authorized_server'] = $serverName;
        $_SESSION['control_panel_token'] = bin2hex(random_bytes(16)); // Generate a random token
        $_SESSION['security_key'] = $decryptedKey; // Store the decrypted key in the session
        header("Location: controlpanel.php?server=" . urlencode($serverName) . "&token=" . $_SESSION['control_panel_token']);
        exit;
    } else {
        echo "<p class='error-message'>Invalid PIN. Please try again.</p>";
    }
}

// Verify token if the page is reloaded
$token = $_GET['token'] ?? '';
if (!isset($_SESSION['control_panel_token']) || $_SESSION['control_panel_token'] !== $token || $_SESSION['authorized_server'] !== $serverName) {
    // Display a form asking for the PIN
    echo "<form method='POST' class='pin-form'>";
    echo "<label for='pin'>Enter PIN to Access Control Panel:</label>";
    echo "<input type='password' id='pin' name='pin' required>";
    echo "<button type='submit' class='btn'>Access Control Panel</button>";
    echo "</form>";
    exit;
}

// Function to execute a command and return the output
function execute_command($command) {
    return shell_exec($command);
}


// Function to save servers data
function save_servers_data($data) {
    $json_file = 'servers_data.json';
    file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT));
}
function start_server($serverName) {
    $servers_data = load_servers_data();
    if (isset($servers_data[$serverName])) {
        $serverDir = $servers_data[$serverName]['directory'];
        $startScript = "$serverDir\\start.bat";
        if (file_exists($startScript)) {
            exec("start \"$serverName\" /D \"$serverDir\" cmd /c $startScript", $output, $return_var);
            if ($return_var === 0) {
                $servers_data[$serverName]['status'] = 'running';
                save_servers_data($servers_data);
                return true;
            }
        }
    }
    return false;
} 


// Handle server actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize_input($_POST['action']);
    $serverName = sanitize_input($_POST['server']);
    
    // Define the path to the server directory
    $serverDir = __DIR__."/minecraft/servers/$serverName";

    $result = false;
    $message = '';

    switch ($action) {
        case 'start':
            $result = start_server($serverName);
            $message = $result ? "Server started successfully." : "Failed to start server.";
            break;
		case 'delete':
            $command = "rm -rf $serverDir";
            if (file_exists(__DIR__ . '/servers_data.json')) {
                $data = json_decode(file_get_contents(__DIR__ . '/servers_data.json'), true);
                unset($data[$serverName]);
                file_put_contents(__DIR__ . '/servers_data.json', json_encode($data, JSON_PRETTY_PRINT));
            }
            break;

    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['success' => $result, 'message' => $message]);
        exit;
    }
}

// New function to get server console output
function get_server_output($serverName) {
    $servers_data = load_servers_data();
    if (isset($servers_data[$serverName])) {
        $serverDir = $servers_data[$serverName]['directory'];
        $logFile = "$serverDir\\logs\\latest.log";
        if (file_exists($logFile)) {
            $lines = file($logFile);
            return implode('', array_slice($lines, -10)); // Get last 10 lines
        }
    }
    return "No output available.";
}
$servers_data = load_servers_data();

// Handle AJAX requests for console output
if (isset($_GET['action']) && $_GET['action'] === 'get_console' && isset($_GET['server'])) {
    echo get_server_output($_GET['server']);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minecraft Server Admin Interface</title>
	
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            background-color: #f4f4f4;
        }
        #sidebar {
            width: 250px;
            background-color: #333;
            color: #fff;
            min-height: 100vh;
            padding: 10px;
            position: fixed;
        }
        #sidebar h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }
        #sidebar a {
            display: block;
            color: #fff;
            text-decoration: none;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        #sidebar a:hover {
            background-color: #444;
        }
        #main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }
        header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 1.5em;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            margin: 20px 0;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 12px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #45a049;
        }
        input[type="text"] {
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        .console-output {
            background-color: #1e1e1e;
            color: #f0f0f0;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
        }
        .rcon-section {
            margin-top: 20px;
            background-color: #fff;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .rcon-section h2 {
            margin-top: 0;
        }
        #rconForm {
            display: grid;
            gap: 10px;
        }
        #rconForm label {
            font-weight: bold;
        }
        #rconForm input[type="text"],
        #rconForm input[type="number"],
        #rconForm input[type="password"] {
            width: 100%;
        }
        .rcon-response {
            margin-top: 15px;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 4px;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
		 <script>
        $(document).ready(function() {
            $('.action-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        alert(response.message);
                        location.reload();
                    },
                    error: function() {
                        alert('An error occurred.');
                    }
                });
            });

            function updateConsoleOutput() {
                $('.console-output').each(function() {
                    var consoleDiv = $(this);
                    var serverName = consoleDiv.data('server');
                    $.get('admin_interface.php', { action: 'get_console', server: serverName }, function(data) {
                        consoleDiv.text(data);
                    });
                });
            }

            // Update console output every 5 seconds
            setInterval(updateConsoleOutput, 5000);
        });
    </script>
</head>
<body>
    <div id="sidebar">
        <h2>Admin Menu</h2>
        <a href="#">Dashboard</a>
        <a href="#">Server Stats</a>
        <a href="#">Configuration</a>
        <a href="#">Logs</a>
        <a href="#">Help</a>
    </div>
    <div id="main-content">
        <header>
            Minecraft Server Admin Interface
        </header>
        <table>
            <thead>
                <tr>
                    <th>Server Name</th>
                    <th>Max Players</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
					<th>Rcon</th>
                    <th>Log</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($servers_data)): ?>
                <tr>
                    <td colspan="5">No servers found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($servers_data as $serverName => $serverInfo): ?>
                <tr>
                    <td><?php echo htmlspecialchars($serverName); ?></td>
                    <td><?php echo htmlspecialchars($serverInfo['max_players']); ?></td>
                    <td id="serverStatus"><?php echo htmlspecialchars($serverInfo['status']); ?></td>
                    <td><?php echo htmlspecialchars($serverInfo['created_at']); ?></td>
                    <td>
                        <form class="action-form" method="post">
                            <input type="hidden" name="server" value="<?php echo htmlspecialchars($serverName); ?>">
                            <input type="hidden" name="action" value="start">
                            <button id="startServer" type="submit">Start</button>
                        </form>

                        <form class="action-form" method="post">
                            <input type="hidden" name="server" value="<?php echo htmlspecialchars($serverName); ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit">Delete</button>
                        </form>
</td>						<td>
						
        <section class="rcon-section">
            <h2>Send RCON Command</h2>
          <form id="rconForm">
    <input type="hidden" name="action" value="sendRconCommand">
    <input type="text" name="serverName" placeholder="Server Name">
    <input type="text" name="rconCommand" placeholder="RCON Command">
    <input type="text" name="rconHost" placeholder="RCON Host">
    <input type="number" name="rconPort" placeholder="RCON Port">
    <input type="text" name="rconPassword" placeholder="RCON Password">
    <button type="submit">Send Command</button>
</form>



            <div id="rconResponse" class="rcon-response">
                <!-- RCON response will be displayed here -->
            </div>
        </section>
						</td>
                    </td>
			<td>
                <div class="console-output" data-server="<?php echo htmlspecialchars($serverName); ?>">
                    <?php echo htmlspecialchars(get_server_output($serverName)); ?>
                </div>
            </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    const rconForm = document.getElementById('rconForm');
    const rconResponse = document.getElementById('rconResponse');

    rconForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(rconForm);
        formData.append('serverName', '<?php echo htmlspecialchars($_GET['server']); ?>');
        formData.append('action', 'sendRconCommand');

        fetch('rcon_command.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Check if the response is JSON
            if (response.ok && response.headers.get('content-type')?.includes('application/json')) {
                return response.json();
            } else {
                return response.text().then(text => {
                    throw new Error(`Unexpected response format: ${text}`);
                });
            }
        })
        .then(data => {
            if (data.success) {
                rconResponse.textContent = `Response: ${data.response}`;
            } else {
                rconResponse.textContent = `Error: ${data.message}`;
            }
        })
        .catch(error => {
            rconResponse.textContent = `Error: ${error.message}`;
        });
    });
});


        // Handle server control actions
        function controlServer(action) {
            fetch('controlpanel.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: action,
                    server: serverName
                })
            })
            .then(response => response.json())
            .then(data => alert(data.message))
            .catch(error => console.error('Error:', error));
        }

        // Bind control buttons
        document.getElementById('startServer').addEventListener('click', function() {
            controlServer('start');
        });

        document.getElementById('stopServer').addEventListener('click', function() {
            controlServer('stop');
        });

        document.getElementById('restartServer').addEventListener('click', function() {
            controlServer('restart');
        });

        // Update server status
        function updateServerStatus(serverName) {
            fetch('controlpanel.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'status',
                    server: serverName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (document.getElementById('serverStatus')) {
                    document.getElementById('serverStatus').textContent = data.status;
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Initial status update
        updateServerStatus(serverName);
</script>

</body>
</html>