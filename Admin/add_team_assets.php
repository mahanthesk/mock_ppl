<?php
require_once '../auth.php';
check_access('auction_admin');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Upload Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>

<body>
    <div class="container">
        <h1>Image Upload Form</h1>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="team_name" class="form-label">Team Name:</label>
                <select class="form-control" id="team_name" name="team_name">
                    <option value="" slected>Select a team</option>
                    <option value="AS Lions">AS Lions</option>
                    <option value="Hoysala Warriors">Hoysala Warriors</option>
                    <option value="JK Panthers">JK Panthers</option>
                    <option value="Lion Kings">Lion Kings</option>
                    <option value="Master Blasters">Master Blasters</option>
                    <option value="Prince 11">Prince 11</option>
                    <option value="Radha Rebels">Radha Rebels</option>
                    <option value="SMK Mysuru Kings">SMK Mysuru Kings</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="team_logo" class="form-label">Team Logo:</label>
                <input type="file" class="form-control" id="team_logo" name="team_logo" accept="image/*" required>
            </div>
            <div class="mb-3">
                <label for="owner" class="form-label">Owner Name:</label>
                <input type="text" class="form-control" id="owner" name="owner" value="owner" placeholder="Enter owner name" required>
            </div>
            <div class="mb-3">
                <label for="owner_image" class="form-label">Owner Image:</label>
                <input type="file" class="form-control" id="owner_image" name="owner_image" accept="image/*" required>
            </div>
            <div class="mb-3">
                <label for="ic_player" class="form-label">Icon Player Name:</label>
                <input type="text" class="form-control" id="ic_player" name="ic_player" value="icon"
                    placeholder="Enter icon player name" required>
            </div>
            <div class="mb-3">
                <label for="icon_image" class="form-label">Icon Player Image:</label>
                <input type="file" class="form-control" id="icon_image" name="icon_image" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>

</html>


<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "spl_season2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $team_name = $_POST['team_name'];
    $owner = $_POST['owner'];
    $ic_player = $_POST['ic_player'];

    $team_logo = $_FILES['team_logo']['name'];
    $owner_image = $_FILES['owner_image']['name'];
    $icon_image = $_FILES['icon_image']['name'];

    // Move uploaded files to team_assets folder with the specified format
    $target_dir = "team_assets/";

    // Generate filenames based on the team name
    $team_logo_filename = $team_name . "_logo." . pathinfo($team_logo, PATHINFO_EXTENSION);
    $owner_image_filename = $team_name . "_owner." . pathinfo($owner_image, PATHINFO_EXTENSION);
    $icon_image_filename = $team_name . "_icon." . pathinfo($icon_image, PATHINFO_EXTENSION);

    // Move files to the target directory
    move_uploaded_file($_FILES['team_logo']['tmp_name'], $target_dir . $team_logo_filename);
    move_uploaded_file($_FILES['owner_image']['tmp_name'], $target_dir . $owner_image_filename);
    move_uploaded_file($_FILES['icon_image']['tmp_name'], $target_dir . $icon_image_filename);

    // Insert the data into the database
    $sql = "INSERT INTO team_master (team_name, owner, ic_player, team_logo, owner_img, ic_player_img)
            VALUES ('$team_name', '$owner', '$ic_player', '$team_logo_filename', '$owner_image_filename', '$icon_image_filename')";

    if ($conn->query($sql) === TRUE) {
        echo "New team inserted successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>