<?php
session_start();
session_regenerate_id(true);

// Redirect if user is not logged in or is not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include("../assest/connection/config.php");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $name = $_POST['name'];
            $latitude = $_POST['latitude'];
            $longitude = $_POST['longitude'];
            $radius = $_POST['radius'];
            $qr_code = $_POST['qr_code'];

            if ($_POST['action'] === 'add') {
                $stmt = $conn->prepare("INSERT INTO office_locations (name, latitude, longitude, radius, qr_code) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sddds", $name, $latitude, $longitude, $radius, $qr_code);
            } else {
                $id = $_POST['id'];
                $stmt = $conn->prepare("UPDATE office_locations SET name = ?, latitude = ?, longitude = ?, radius = ?, qr_code = ? WHERE id = ?");
                $stmt->bind_param("sdddsi", $name, $latitude, $longitude, $radius, $qr_code, $id);
            }

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Office location " . ($_POST['action'] === 'add' ? "added" : "updated") . " successfully.";
            } else {
                $_SESSION['error_message'] = "Error: " . $stmt->error;
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM office_locations WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Office location deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    header("Location: office_locations.php");
    exit();
}

// Fetch all office locations
$result = $conn->query("SELECT * FROM office_locations ORDER BY name");
$locations = $result->fetch_all(MYSQLI_ASSOC);

include("include/header.php");
include("include/topbar.php");
$activePage = 'office_locations';
include("include/sidebar.php");
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Office Locations</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">Office Locations</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Manage Office Locations</h3>
                        </div>
                        <div class="card-body">
                            <?php
                            if (isset($_SESSION['success_message'])) {
                                echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                                unset($_SESSION['success_message']);
                            }
                            if (isset($_SESSION['error_message'])) {
                                echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
                                unset($_SESSION['error_message']);
                            }
                            ?>
                            <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#addLocationModal">
                                Add New Location
                            </button>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Latitude</th>
                                        <th>Longitude</th>
                                        <th>Radius (km)</th>
                                        <th>QR Code</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($locations as $location) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($location['name']); ?></td>
                                            <td><?php echo $location['latitude']; ?></td>
                                            <td><?php echo $location['longitude']; ?></td>
                                            <td><?php echo $location['radius']; ?></td>
                                            <td><?php echo htmlspecialchars($location['qr_code']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary edit-location" data-toggle="modal" data-target="#editLocationModal" data-location='<?php echo json_encode($location); ?>'>
                                                    Edit
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $location['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this location?')">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Add Location Modal -->
<div class="modal fade" id="addLocationModal" tabindex="-1" role="dialog" aria-labelledby="addLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLocationModalLabel">Add New Location</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="latitude">Latitude</label>
                        <input type="number" step="any" class="form-control" id="latitude" name="latitude" required>
                    </div>
                    <div class="form-group">
                        <label for="longitude">Longitude</label>
                        <input type="number" step="any" class="form-control" id="longitude" name="longitude" required>
                    </div>
                    <div class="form-group">
                        <label for="radius">Radius (km)</label>
                        <input type="number" step="0.001" class="form-control" id="radius" name="radius" required>
                    </div>
                    <div class="form-group">
                        <label for="qr_code">QR Code</label>
                        <input type="text" class="form-control" id="qr_code" name="qr_code" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Location</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Location Modal -->
<div class="modal fade" id="editLocationModal" tabindex="-1" role="dialog" aria-labelledby="editLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLocationModalLabel">Edit Location</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group">
                        <label for="edit_name">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_latitude">Latitude</label>
                        <input type="number" step="any" class="form-control" id="edit_latitude" name="latitude" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_longitude">Longitude</label>
                        <input type="number" step="any" class="form-control" id="edit_longitude" name="longitude" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_radius">Radius (km)</label>
                        <input type="number" step="0.001" class="form-control" id="edit_radius" name="radius" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_qr_code">QR Code</label>
                        <input type="text" class="form-control" id="edit_qr_code" name="qr_code" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Location</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include("include/footer.php");
?>

<script>
    $(document).ready(function() {
        $('.edit-location').click(function() {
            var location = $(this).data('location');
            $('#edit_id').val(location.id);
            $('#edit_name').val(location.name);
            $('#edit_latitude').val(location.latitude);
            $('#edit_longitude').val(location.longitude);
            $('#edit_radius').val(location.radius);
            $('#edit_qr_code').val(location.qr_code);
        });
    });
</script>