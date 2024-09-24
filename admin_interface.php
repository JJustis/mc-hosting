<?php
// Set the username and password for basic authentication
define('USERNAME', 'admin');
define('PASSWORD', 'password123x');

// Check if the user is authenticated
if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== USERNAME || $_SERVER['PHP_AUTH_PW'] !== PASSWORD) {
    header('HTTP/1.0 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Restricted Area"');
    echo 'You need to enter a valid username and password.';
    exit;
}

// Your existing PHP code follows...
error_reporting(E_ALL);
ini_set('display_errors', 1);
//header('Content-Type: application/json');

// Function to load servers data
function load_servers_data() {
    $json_file = 'servers_data.json';
    if (file_exists($json_file)) {
        return json_decode(file_get_contents($json_file), true);
    }
    return [];
}

// Function to save servers data
function save_servers_data($data) {
    $json_file = 'servers_data.json';
    file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT));
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

function stop_server($serverName) {
    $servers_data = load_servers_data();
    if (isset($servers_data[$serverName])) {
        $serverDir = $servers_data[$serverName]['directory'];
        $consoleFile = "$serverDir\\server_console.txt"; // This should be where your server writes its output
        $command = "echo /stop > $consoleFile";
        
        exec($command, $output, $return_var);
        
        if ($return_var === 0) {
            sleep(5); // Give the server some time to stop
            $servers_data[$serverName]['status'] = 'stopped';
            save_servers_data($servers_data);
            return true;
        }
    }
    return false;
}

// Function to delete the server
function delete_server($serverName) {
    $servers_data = load_servers_data();
    if (isset($servers_data[$serverName])) {
        $serverDir = $servers_data[$serverName]['directory'];
        stop_server($serverName);
        if (is_dir($serverDir)) {
            array_map('unlink', glob("$serverDir/*.*"));
            rmdir($serverDir);
        }
        unset($servers_data[$serverName]);
        save_servers_data($servers_data);
        return true;
    }
    return false;
}

// Function to op a player
function op_player($serverName, $playerName) {
    $servers_data = load_servers_data();
    if (isset($servers_data[$serverName])) {
        $serverDir = $servers_data[$serverName]['directory'];
        $opFile = "$serverDir\\ops.json";
        $ops = json_decode(file_get_contents($opFile), true) ?? [];
        $ops[] = ['uuid' => '', 'name' => $playerName, 'level' => 4];
        file_put_contents($opFile, json_encode($ops, JSON_PRETTY_PRINT));
        $servers_data[$serverName]['ops'][] = $playerName;
        save_servers_data($servers_data);
        return true;
    }
    return false;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $serverName = $_POST['server'] ?? '';
    $playerName = $_POST['player'] ?? '';

    $result = false;
    $message = '';

    switch ($action) {
        case 'start':
            $result = start_server($serverName);
            $message = $result ? "Server started successfully." : "Failed to start server.";
            break;
        case 'stop':
            $result = stop_server($serverName);
            $message = $result ? "Server stopped successfully." : "Failed to stop server.";
            break;
        case 'delete':
            $result = delete_server($serverName);
            $message = $result ? "Server deleted successfully." : "Failed to delete server.";
            break;
        case 'op':
            $result = op_player($serverName, $playerName);
            $message = $result ? "Player opped successfully." : "Failed to op player.";
            break;
    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['success' => $result, 'message' => $message]);
        exit;
    }
}

// Load server data
$servers_data = load_servers_data();

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
            padding: 20px;
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
    <h1>Minecraft Server Admin Interface</h1>
    <table>
        <tr>
            <th>Server Name</th>
            <th>Max Players</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Actions</th>
			<th>rcon</th>
            <th>Console Output</th>
        </tr>
        <?php if (empty($servers_data)): ?>
        <tr>
            <td colspan="6">No servers found.</td>
        </tr>
        <?php else: ?>
        <?php foreach ($servers_data as $serverName => $serverInfo): ?>
        <tr>
            <td><?php echo htmlspecialchars($serverName); ?></td>
            <td><?php echo htmlspecialchars($serverInfo['max_players']); ?></td>
            <td><?php echo htmlspecialchars($serverInfo['status']); ?></td>
            <td><?php echo htmlspecialchars($serverInfo['created_at']); ?></td>
            <td>
                <form class="action-form" method="post">
                    <input type="hidden" name="server" value="<?php echo htmlspecialchars($serverName); ?>">
                    <input type="hidden" name="action" value="start">
                    <button type="submit">Start</button>
                </form>
                <form class="action-form" method="post">
                    <input type="hidden" name="server" value="<?php echo htmlspecialchars($serverName); ?>">
                    <input type="hidden" name="action" value="stop">
                    <button type="submit">Stop</button>
                </form>
                <form class="action-form" method="post">
                    <input type="hidden" name="server" value="<?php echo htmlspecialchars($serverName); ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit">Delete</button>
                </form>
<div class="action-form" class="rcon-section">
    <h2>RCON Command</h2>
    <form id="rconForm">
        <input type="hidden" name="serverName" value="<?php echo htmlspecialchars($serverName); ?>">
        <label for="rconHost">RCON Host:</label>
        <input type="text" id="rconHost" name="rconHost" value="127.0.0.1" required>
        <label for="rconPort">RCON Port:</label>
        <input type="number" id="rconPort" name="rconPort" value="25575" required>
        <label for="rconPassword">RCON Password:</label>
        <input type="password" id="rconPassword" name="rconPassword" required>
        <label for="rconCommand">Command:</label>
        <input type="text" id="rconCommand" name="rconCommand" required>
        <button type="submit">Send Command</button>
    </form>
    <div class="rcon-response" id="rconResponse"></div>
</div>



            </td> <td>   <h2>Op a Player</h2>
    <form class="action-form" method="post">
        <input type="hidden" name="action" value="op">
        <label for="server">Server Name:</label>
        <input type="text" id="server" name="server" required>
        <label for="player">Player Name:</label>
        <input type="text" id="player" name="player" required>
        <button type="submit">Op Player</button>
    </form></td>
            <td class="console-output" data-server="<?php echo htmlspecialchars($serverName); ?>"></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </table>
<script>
$(document).ready(function() {
    $('#rconForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'rcon2.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $('#rconResponse').html('<p>' + response.message + '</p>');
            },
            error: function() {
                $('#rconResponse').html('<p>An error occurred while sending the command.</p>');
            }
        });
    });
});

</script>
</body>
</html>
