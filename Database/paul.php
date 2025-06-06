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

if (isset($_POST['add_patient_presc'])) {
    $pres_doc_number = $_SESSION['doc_number']; // Doctor number from session
    $pres_pat_number = $_POST['pres_pat_number']; // From hidden input
    $medicine_names = $_POST['medicine_name'];
    $patterns = $_POST['pattern'];
    $durations = $_POST['duration'];

    $query = "INSERT INTO his_doc_prescriptions (pres_pat_number, pres_doc_number, pres_med_name, pres_med_pattern, pres_med_duration) VALUES (?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);

    for ($i = 0; $i < count($medicine_names); $i++) {
        $med_name = $medicine_names[$i];
        $pattern = $patterns[$i];
        $duration = $durations[$i];

        $stmt->bind_param("sssss", $pres_pat_number, $pres_doc_number, $med_name, $pattern, $duration);
        $stmt->execute();
    }

    if ($stmt) {
        $success = "Prescriptions Added Successfully";
    } else {
        $err = "Error. Try Again.";
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

                       
                            <?php
                            $pat_number = $_GET['pat_number'];
                            $ret = "SELECT * FROM his_patients WHERE pat_number=?";
                            $stmt = $mysqli->prepare($ret);
                            $stmt->bind_param('s', $pat_number);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($row = $res->fetch_object()) {
                            ?>
                                <div class="card-box">
                                    <input type="hidden" name="pres_pat_name" value="<?php echo $row->pat_fname . ' ' . $row->pat_lname; ?>">
                                            
                                    <input type="hidden" name="pres_pat_number" value="<?php echo $row->pat_number; ?>">

                                    <div class="form-group">
                                        <label>Prescriptions List</label>
                                        <div class="row font-weight-bold text-secondary mb-2">
                                            <div class="col-2">Medicine</div>
                                            <div class="col-1">Pattern</div>
                                            <div class="col-1">Duration</div>
                                            <div class="col-1 text-center">Action</div>
                                        </div>
                                        <div id="prescriptions_display"></div>
                                        <button type="button" class="btn btn-outline-success mt-3" data-toggle="modal" data-target="#prescriptionModal">
                                            <i class="fa fa-plus"></i> Add Prescription
                                        </button>
                                    </div>

                                    <div id="hidden-fields"></div>

                                    <br>
                                    <button type="submit" name="add_patient_presc" class="btn btn-primary">Submit Prescription</button>

                                    <<div class="modal fade" id="prescriptionModal" tabindex="-1" role="dialog" aria-labelledby="prescriptionModalLabel" aria-hidden="true">
                                    
                                    <!-- Add pres? -->
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="prescriptionModalLabel">Add Prescription</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-row">
                                                <div class="form-group col-md-5">
                                                    <label>Medicine</label>
                                                    <input type="text" id="modal-medicine" class="form-control" required>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label>Pattern</label>
                                                    <input type="text" id="modal-pattern" class="form-control" required>
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label>Duration</label>
                                                    <input type="number" id="modal-duration" class="form-control" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary btn-sm" id="add-to-list">Add</button>
                                        </div>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                             <?php } ?>

                            <div class="card-box">
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
<script>
$(document).ready(function () {
    let index = 0;

    $('#add-to-list').click(function () {
        const medicine = $('#modal-medicine').val().trim();
        const pattern = $('#modal-pattern').val().trim();
        const duration = $('#modal-duration').val().trim();

        if (medicine && pattern && duration) {
            $('#prescriptions_display').append(`
                <div class="row mb-2 align-items-center" id="prescription-${index}">
                    <div class="col-2"><small>${medicine}</small></div>
                    <div class="col-1"><small>${pattern}</small></div>
                    <div class="col-1"><small>${duration}</small></div>
                    <div class="col-1 text-center">
                        <button type="button" class="btn btn-sm btn-danger remove-prescription" data-id="${index}">X</button>
                    </div>
                </div>
            `);

            $('#hidden-fields').append(`
                <div id="hidden-${index}">
                    <input type="hidden" name="medicine_name[]" value="${medicine}">
                    <input type="hidden" name="pattern[]" value="${pattern}">
                    <input type="hidden" name="duration[]" value="${duration}">
                </div>
            `);

            index++;

            // Clear modal fields
            $('#modal-medicine').val('');
            $('#modal-pattern').val('');
            $('#modal-duration').val('');

            // Hide modal and clean up backdrop
            $('#prescriptionModal').modal('hide');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        }
    });

    // Handle removal of dynamically added prescriptions
    $(document).on('click', '.remove-prescription', function () {
        const id = $(this).data('id');
        $(`#prescription-${id}`).remove();
        $(`#hidden-${id}`).remove();
    });
});
</script>
</body>

</html>