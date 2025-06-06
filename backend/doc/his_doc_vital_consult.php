<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();

$doc_id = $_SESSION['doc_id'];
$pat_number = $_GET['pat_number'] ?? '';
$redirect_url = $_SERVER['PHP_SELF'] . '?pat_number=' . urlencode($pat_number);

// === Handle Form Submissions ===

// Add Vitals
if (isset($_POST['add_patient_vitals'])) {
    $vit_number = $_POST['vit_number'];
    $vit_bodytemp = $_POST['vit_bodytemp'];
    $vit_heartpulse = $_POST['vit_heartpulse'];
    $vit_resprate = $_POST['vit_resprate'];
    $vit_bloodpress = $_POST['vit_bloodpress'];

    $stmt = $mysqli->prepare("INSERT INTO his_vitals 
        (vit_number, vit_pat_number, vit_bodytemp, vit_heartpulse, vit_resprate, vit_bloodpress) 
        VALUES (?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        $stmt->bind_param('ssssss', $vit_number, $pat_number, $vit_bodytemp, $vit_heartpulse, $vit_resprate, $vit_bloodpress);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Patient vitals added successfully.";
        } else {
            $_SESSION['err'] = "Failed to add vitals.";
        }
    } else {
        $_SESSION['err'] = "Preparation failed for vitals.";
    }

    header("Location: $redirect_url");
    exit();
}

// Add Prescriptions
if (isset($_POST['add_patient_presc'])) {
    $pres_doc_number = $_SESSION['doc_number'];
    $pres_pat_number = $_POST['pres_pat_number'];
    $medicine_names = $_POST['medicine_name'];
    $patterns = $_POST['pattern'];
    $durations = $_POST['duration'];

    $stmt = $mysqli->prepare("INSERT INTO his_doc_prescriptions 
        (pres_pat_number, pres_doc_number, pres_med_name, pres_med_pattern, pres_med_duration) 
        VALUES (?, ?, ?, ?, ?)");

    if ($stmt) {
        $success_all = true;
        for ($i = 0; $i < count($medicine_names); $i++) {
            $stmt->bind_param("sssss", $pres_pat_number, $pres_doc_number, $medicine_names[$i], $patterns[$i], $durations[$i]);
            if (!$stmt->execute()) {
                $success_all = false;
                break;
            }
        }
        $_SESSION[$success_all ? 'success' : 'err'] = $success_all
            ? "Prescriptions added successfully."
            : "Failed to add all prescriptions.";
    } else {
        $_SESSION['err'] = "Preparation failed for prescriptions.";
    }

    header("Location: $redirect_url");
    exit();
}

// Add Visit Notes
if (isset($_POST['add_doctor_notes'])) {
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

    $stmt = $mysqli->prepare("INSERT INTO his_visits 
        (pat_number, doc_id, complaints, presenting_illness, medical_history, surgical_history, family_history, social_history, economic_history, allergies, impressions, diagnosis, clinical_summary) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt) {
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

        if ($stmt->execute()) {
            $_SESSION['success'] = "Visit record added successfully.";
        } else {
            $_SESSION['err'] = "Failed to add visit record.";
        }
    } else {
        $_SESSION['err'] = "Preparation failed for visit notes.";
    }

    header("Location: $redirect_url");
    exit();
}

// === Display Session Messages ===


?>



<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>

<body>
    <?php if (isset($_SESSION['success']) || isset($_SESSION['err'])): ?>
    <div id="alert-message" class="alert <?= isset($_SESSION['success']) ? 'alert-success' : 'alert-danger' ?>" role="alert">
        <?= $_SESSION['success'] ?? $_SESSION['err'] ?>
    </div>
    <script>
        setTimeout(() => {
            const alertBox = document.getElementById('alert-message');
            if (alertBox) {
                alertBox.style.transition = 'opacity 0.5s ease-out';
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 500);
            }
        }, 3000);
    </script>
    <?php unset($_SESSION['success'], $_SESSION['err']); ?>
<?php endif; ?>

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

                            <div class="col-lg-4 col-xl-4">
                                <div class="card-box text-center">
                                    <img src="assets/images/users/patient.png" class="rounded-circle avatar-lg img-thumbnail"
                                        alt="profile-image">


                                    <div class="text-left mt-3">

                                        <p class="text-muted mb-2 font-13"><strong>Full Name :</strong> <span class="ml-2"><?php echo $row->pat_fname; ?> <?php echo $row->pat_lname; ?></span></p>
                                        <p class="text-muted mb-2 font-13"><strong>Mobile :</strong><span class="ml-2"><?php echo $row->pat_phone; ?></span></p>
                                        <p class="text-muted mb-2 font-13"><strong>Address :</strong> <span class="ml-2"><?php echo $row->pat_addr; ?></span></p>
                                        <p class="text-muted mb-2 font-13"><strong>Date Of Birth :</strong> <span class="ml-2"><?php echo $row->pat_dob; ?></span></p>
                                        <p class="text-muted mb-2 font-13"><strong>Age :</strong> <span class="ml-2"><?php echo $row->pat_age; ?> Years</span></p>
                                        <p class="text-muted mb-2 font-13"><strong>Ailment :</strong> <span class="ml-2"><?php echo $row->pat_ailment; ?></span></p>
                                        <hr>

                                        <hr>




                                    </div>

                                </div> <!-- end card-box -->

                            </div> <!-- end col-->
                        <?php } ?>
                        <div class="col-lg-8 col-xl-8">
                            <div class="card-box">
                                <ul class="nav nav-pills navtab-bg nav-justified">
                                    <li class="nav-item">
                                        <a href="#aboutme" data-toggle="tab" aria-expanded="false" class="nav-link active">
                                            Prescription
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#timeline" data-toggle="tab" aria-expanded="true" class="nav-link ">
                                            Vitals
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#settings" data-toggle="tab" aria-expanded="false" class="nav-link">
                                            Lab Records
                                        </a>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane show active" id="aboutme">
                                        <ul class="list-unstyled timeline-sm">
                                            <?php
                                            $pres_pat_number = $_GET['pat_number'];
                                            $ret = "SELECT  * FROM his_prescriptions WHERE pres_pat_number = '$pres_pat_number'";
                                            $stmt = $mysqli->prepare($ret);
                                            // $stmt->bind_param('i',$pres_pat_number );
                                            $stmt->execute(); //ok
                                            $res = $stmt->get_result();
                                            //$cnt=1;

                                            while ($row = $res->fetch_object()) {
                                                $mysqlDateTime = $row->pres_date; //trim timestamp to date

                                            ?>
                                                <li class="timeline-sm-item">
                                                    <span class="timeline-sm-date"><?php echo date("Y-m-d", strtotime($mysqlDateTime)); ?></span>
                                                    <h5 class="mt-0 mb-1"><?php echo $row->pres_pat_ailment; ?></h5>
                                                    <p class="text-muted mt-2">
                                                        <?php echo $row->pres_ins; ?>
                                                    </p>

                                                </li>
                                            <?php } ?>
                                        </ul>

                                    </div> <!-- end tab-pane -->
                                    <!-- end Prescription section content -->

                                    <div class="tab-pane show " id="timeline">
                                        <div class="table-responsive">
                                            <table class="table table-borderless mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Body Temperature</th>
                                                        <th>Heart Rate/Pulse</th>
                                                        <th>Respiratory Rate</th>
                                                        <th>Blood Pressure</th>
                                                        <th>Date Recorded</th>
                                                    </tr>
                                                </thead>
                                                <?php
                                                $vit_pat_number = $_GET['pat_number'];
                                                $ret = "SELECT  * FROM his_vitals WHERE vit_pat_number = '$vit_pat_number'";
                                                $stmt = $mysqli->prepare($ret);
                                                // $stmt->bind_param('i',$vit_pat_number );
                                                $stmt->execute(); //ok
                                                $res = $stmt->get_result();
                                                //$cnt=1;

                                                while ($row = $res->fetch_object()) {
                                                    $mysqlDateTime = $row->vit_daterec; //trim timestamp to date

                                                ?>
                                                    <tbody>
                                                        <tr>
                                                            <td><?php echo $row->vit_bodytemp; ?>°C</td>
                                                            <td><?php echo $row->vit_heartpulse; ?>BPM</td>
                                                            <td><?php echo $row->vit_resprate; ?>bpm</td>
                                                            <td><?php echo $row->vit_bloodpress; ?>mmHg</td>
                                                            <td><?php echo date("Y-m-d", strtotime($mysqlDateTime)); ?></td>
                                                        </tr>
                                                    </tbody>
                                                <?php } ?>
                                            </table>
                                        </div>
                                    </div>
                                    <!-- end vitals content-->

                                    <div class="tab-pane" id="settings">
                                        <ul class="list-unstyled timeline-sm">
                                            <?php
                                            $lab_pat_number = $_GET['pat_number'];
                                            $ret = "SELECT  * FROM his_laboratory WHERE  	lab_pat_number  ='$lab_pat_number'";
                                            $stmt = $mysqli->prepare($ret);
                                            // $stmt->bind_param('i',$lab_pat_number);
                                            $stmt->execute(); //ok
                                            $res = $stmt->get_result();
                                            //$cnt=1;

                                            while ($row = $res->fetch_object()) {
                                                $mysqlDateTime = $row->lab_date_rec; //trim timestamp to date

                                            ?>
                                                <li class="timeline-sm-item">
                                                    <span class="timeline-sm-date"><?php echo date("Y-m-d", strtotime($mysqlDateTime)); ?></span>
                                                    <h3 class="mt-0 mb-1"><?php echo $row->lab_pat_ailment; ?></h3>
                                                    <hr>
                                                    <h5>
                                                        Laboratory Tests
                                                    </h5>

                                                    <p class="text-muted mt-2">
                                                        <?php echo $row->lab_pat_tests; ?>
                                                    </p>
                                                    <hr>
                                                    <h5>
                                                        Laboratory Results
                                                    </h5>

                                                    <p class="text-muted mt-2">
                                                        <?php echo $row->lab_pat_results; ?>
                                                    </p>
                                                    <hr>

                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </div>
                                <!-- end lab records content-->

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
                                   <form method="POST">
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
                                    </form> 

                                    <div class="modal fade" id="prescriptionModal" tabindex="-1" role="dialog" aria-labelledby="prescriptionModalLabel" aria-hidden="true">
                                    
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