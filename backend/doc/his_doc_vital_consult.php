<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$doc_id = $_SESSION['doc_id'];

// Insert patient vitals
if (isset($_POST['add_patient_vitals'])) {
    $vit_number = $_POST['vit_number'];
    $vit_pat_number = $_GET['pat_number'];
    $vit_bodytemp  = $_POST['vit_bodytemp'];
    $vit_heartpulse = $_POST['vit_heartpulse'];
    $vit_resprate  = $_POST['vit_resprate'];
    $vit_bloodpress = $_POST['vit_bloodpress'];

    $query = "INSERT INTO his_vitals 
        (vit_number, vit_pat_number, vit_bodytemp, vit_heartpulse, vit_resprate, vit_bloodpress)
        VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssssss', $vit_number, $vit_pat_number, $vit_bodytemp, $vit_heartpulse, $vit_resprate, $vit_bloodpress);
    $stmt->execute();

    if ($stmt) {
        $success = "Patient Vitals Added";
    } else {
        $err = "Please Try Again Later";
    }
}

// Insert visit notes
if (isset($_POST['add_doctor_notes'])) {
    $pat_number = $_GET['pat_number'];

    // Fetch pat_id from pat_number
    // $pat_id = 0;
    // $stmt_pat = $mysqli->prepare("SELECT pat_id FROM his_patients WHERE pat_number = ? LIMIT 1");
    // $stmt_pat->bind_param('s', $pat_number);
    // $stmt_pat->execute();
    // $res_pat = $stmt_pat->get_result();
    // if ($row_pat = $res_pat->fetch_assoc()) {
    //     $pat_id = $row_pat['pat_id'];
    // }

    // Collect form data
    $pat_number = $_GET['pat_number'];
    $complaints = $_POST['complaints'];
    $presenting_illness = $_POST['presenting_illness'];
    $medical_history = $_POST['medical_history'];
    $surgical_history = $_POST['surgical_history'];
    $family_history = $_POST['family_history'];
    $social_history = $_POST['social_history'];
    $economic_history = $_POST['economic_history'];
    $allergies = $_POST['allergies'];
    $impressions = $_POST['impressions'];
    $diagnosis = $_POST['diagnosis'];
    $clinical_summary = $_POST['clinical_summary'];

    // Insert into his_visits
    $query = "INSERT INTO his_visits 
        (pat_number, doc_id, complaints, presenting_illness, medical_history, surgical_history, family_history, social_history, economic_history, allergies, impressions, diagnosis, clinical_summary)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param(
        'sisssssssssss',
        $pat_number,
        $doc_id,
        $complaints,
        $presenting_illness,
        $medical_history,
        $surgical_history,
        $family_history,
        $social_history,
        $economic_history,
        $allergies,
        $impressions,
        $diagnosis,
        $clinical_summary
    );
    $stmt->execute();

    if ($stmt) {
        $success = "Visit record successfully added.";
    } else {
        $err = "Error adding visit record.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>

<body>
    <div id="wrapper">
        <?php include("assets/inc/nav.php"); ?>
        <?php include("assets/inc/sidebar.php"); ?>

        <?php
        $pat_number = $_GET['pat_number'];
        $ret = "SELECT * FROM his_patients WHERE pat_number=?";
        $stmt = $mysqli->prepare($ret);
        $stmt->bind_param('s', $pat_number);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_object()) {
        ?>

            <div class="content-page">
                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box">
                                    <div class="page-title-right">
                                        <ol class="breadcrumb m-0">
                                            <li class="breadcrumb-item"><a href="his_doc_dashboard.php">Dashboard</a></li>
                                            <li class="breadcrumb-item"><a href="javascript: void(0);">Laboratory</a></li>
                                            <li class="breadcrumb-item active">Capture Vitals</li>
                                        </ol>
                                    </div>
                                    <h4 class="page-title">Capture <?php echo $row->pat_fname . ' ' . $row->pat_lname; ?> Vitals</h4>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="header-title">Fill all fields</h4>

                                        <form method="post">
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label class="col-form-label">Patient Name</label>
                                                    <input type="text" readonly class="form-control" value="<?php echo $row->pat_fname . ' ' . $row->pat_lname; ?>">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label class="col-form-label">Patient Ailment</label>
                                                    <input type="text" readonly class="form-control" value="<?php echo $row->pat_ailment; ?>">
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-12">
                                                    <label class="col-form-label">Patient Number</label>
                                                    <input type="text" name="vit_pat_number" required readonly class="form-control" value="<?php echo $row->pat_number; ?>">
                                                </div>
                                            </div>

                                            <div class="form-row" style="display:none">
                                                <?php $vit_no = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 1, 5); ?>
                                                <div class="form-group col-md-2">
                                                    <label class="col-form-label">Vital Number</label>
                                                    <input type="text" name="vit_number" value="<?php echo $vit_no; ?>" class="form-control">
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-4">
                                                    <label class="col-form-label">Complaints</label>
                                                    <input type="text" class="form-control" name="complaints">
                                                </div>
                                                <div class="form-group col-md-8">
                                                    <label class="col-form-label">History of Presenting Illness</label>
                                                    <textarea class="form-control" name="presenting_illness"></textarea>
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label class="col-form-label">Medical History</label>
                                                    <input type="text" class="form-control" name="medical_history">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label class="col-form-label">Surgical History</label>
                                                    <input type="text" class="form-control" name="surgical_history">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label class="col-form-label">Family History</label>
                                                    <input type="text" class="form-control" name="family_history">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label class="col-form-label">Social History</label>
                                                    <input type="text" class="form-control" name="social_history">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label class="col-form-label">Economic History</label>
                                                    <input type="text" class="form-control" name="economic_history">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label class="col-form-label">Allergies</label>
                                                    <input type="text" class="form-control" name="allergies">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label class="col-form-label">Impressions</label>
                                                    <input type="text" class="form-control" name="impressions">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label class="col-form-label">Diagnosis</label>
                                                    <input type="text" class="form-control" name="diagnosis">
                                                </div>
                                                <div class="form-group col-md-12">
                                                    <label class="col-form-label">Clinical Summary</label>
                                                    <textarea class="form-control" name="clinical_summary"></textarea>
                                                </div>
                                            </div>

                                            <button type="submit" name="add_doctor_notes" class="btn btn-success">Save</button>
                                        </form>

                                        <hr>
                                            <h4 class="header-title mt-4">Patient’s Previous Visits</h4>
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Visit ID</th>
                                                            <th>Date of Visit</th>
                                                            <th>Note</th>
                                                            <th>Summary Report</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $pat_number = $_GET['pat_number'];
                                                        $query = "SELECT visit_id, visit_date, presenting_illness, clinical_summary FROM his_visits WHERE pat_number = ? ORDER BY visit_date DESC";
                                                        $stmt = $mysqli->prepare($query);
                                                        $stmt->bind_param('s', $pat_number);
                                                        $stmt->execute();
                                                        $result = $stmt->get_result();

                                                        if ($result->num_rows > 0) {
                                                            while ($visit = $result->fetch_assoc()) {
                                                                echo "<tr>
                                                                    <td>{$visit['visit_id']}</td>
                                                                    <td>" . date('d M Y H:i', strtotime($visit['visit_date'])) . "</td>
                                                                    <td>" . htmlspecialchars($visit['presenting_illness']) . "</td>
                                                                    <td>" . htmlspecialchars($visit['clinical_summary']) . "</td>
                                                                </tr>";
                                                            }
                                                        } else {
                                                            echo "<tr><td colspan='4' class='text-center'>No data available in table</td></tr>";
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include('assets/inc/footer.php'); ?>
            </div>
        <?php } ?>
    </div>

    <div class="rightbar-overlay"></div>

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
    <script src="assets/libs/ladda/spin.js"></script>
    <script src="assets/libs/ladda/ladda.js"></script>
    <script src="assets/js/pages/loading-btn.init.js"></script>
</body>

</html>
