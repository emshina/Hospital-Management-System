<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$doc_id = $_SESSION['ad_id'];

$phar_id = $_GET['phar_id'] ?? null;
$unit = $_GET['unit'] ?? null;
$phar_name = $_GET['phar_name'] ?? '';


if (!$phar_id || !$unit) {
    header("location: his_admin_assign_dose.php");
    exit();
}

if (isset($_POST['update_dosage'])) {
    $dosage_pattern = $_POST['dosage_pattern'];
    $duration_days = $_POST['duration_days'];
    $notes = $_POST['notes'];

    // Check if entry exists
    $check = $mysqli->prepare("SELECT * FROM his_pharma_dosage WHERE phar_id = ? AND unit_of_measure = ?");
    $check->bind_param('is', $phar_id, $unit);
    $check->execute();
    $result = $check->get_result();

    // $name_stmt = $mysqli->prepare("SELECT phar_name FROM his_pharmaceuticals WHERE phar_id = ?");
    // $name_stmt->bind_param('i', $phar_id);
    // $name_stmt->execute();
    // $name_res = $name_stmt->get_result();
    // $name_data = $name_res->fetch_assoc();
    // $phar_name = $name_data['phar_name'] ?? '';

    if ($result->num_rows > 0) {
        $query = "UPDATE his_pharma_dosage SET dosage_pattern = ?, duration_days = ?, notes = ? WHERE phar_id = ? AND unit_of_measure = ?";
        $stmt = $mysqli->prepare($query);
        // $stmt->bind_param('sisis', $dosage_pattern, $duration_days, $notes, $phar_id, $unit);
        $stmt->bind_param('sisss', $dosage_pattern, $duration_days, $notes, $phar_id, $unit);

    } else {
            $query = "INSERT INTO his_pharma_dosage (phar_id, phar_name, unit_of_measure, dosage_pattern, duration_days, notes) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('isssis', $phar_id, $phar_name, $unit, $dosage_pattern, $duration_days, $notes);


    }

    // $stmt->execute();
    if ($stmt->execute()) {
        header("location: his_admin_assign_dose.php");
        exit();
    } else {
        $err = "Failed to update dosage details. Error: " . $stmt->error;
    }

}

$query = $mysqli->prepare("SELECT p.phar_name, s.unit_of_measure, d.dosage_pattern, d.duration_days, d.notes FROM his_pharmaceuticals p LEFT JOIN his_pharma_stock s ON p.phar_id = s.phar_id LEFT JOIN his_pharma_dosage d ON p.phar_id = d.phar_id AND s.unit_of_measure = d.unit_of_measure WHERE p.phar_id = ? AND s.unit_of_measure = ? LIMIT 1");
$query->bind_param('is', $phar_id, $unit);
$query->execute();
$res = $query->get_result();
$data = $res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>
<body>
    <div id="wrapper">
        <?php include('assets/inc/nav.php'); ?>
        <?php include("assets/inc/sidebar.php"); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Pharmaceuticals</a></li>
                                        <li class="breadcrumb-item active">Update Dosage</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Update Dosage</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card-box">
                                <h4 class="header-title">Dosage Details</h4>
                                <form method="post">
                                    <div class="form-group">
                                        <label>Pharmaceutical Name</label>
                                        <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($data['phar_name'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Unit of Measure</label>
                                        <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($data['unit_of_measure'] ?? $unit); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Dosage Pattern</label>
                                        <input type="text" name="dosage_pattern" required class="form-control" value="<?php echo htmlspecialchars($data['dosage_pattern'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Duration (in Days)</label>
                                        <input type="number" name="duration_days" required class="form-control" value="<?php echo htmlspecialchars($data['duration_days'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Notes</label>
                                        <textarea name="notes" class="form-control"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
                                    </div>
                                    <button type="submit" name="update_dosage" class="btn btn-primary">Save Changes</button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include('assets/inc/footer.php'); ?>
        </div>
    </div>

    <div class="rightbar-overlay"></div>

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/libs/footable/footable.all.min.js"></script>
    <script src="assets/js/pages/foo-tables.init.js"></script>
    <script src="assets/js/app.min.js"></script>
</body>
</html>
