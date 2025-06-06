<?php 
session_start();
include('assets/inc/config.php');

if (isset($_POST['add_patient_presc'])) {
    $pres_doc_number = $_SESSION['doc_number'];
    $pres_pat_number = $_POST['pres_pat_number'];
    $medicine_names = $_POST['medicine_name'];
    $patterns = $_POST['pattern'];
    $durations = $_POST['duration'];
    $dosages = $_POST['dosage'];

    $query = "INSERT INTO his_doc_prescriptions (pres_pat_number, pres_doc_number, pres_med_name, pres_med_pattern, pres_med_duration, pres_med_dosage) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);

    for ($i = 0; $i < count($medicine_names); $i++) {
        $stmt->bind_param(
            "ssssss",
            $pres_pat_number,
            $pres_doc_number,
            $medicine_names[$i],
            $patterns[$i],
            $durations[$i],
            $dosages[$i]
        );
        $stmt->execute();
    }

    if ($stmt) {
        $success = "Prescriptions Added Successfully";
    } else {
        $err = "Error. Try Again.";
    }
}

// Fetch available medicine names from his_pharmaceuticals
$med_names = [];
$med_query = $mysqli->query("SELECT DISTINCT phar_name FROM his_pharmaceuticals ORDER BY phar_name ASC");
while ($row = $med_query->fetch_assoc()) {
    $med_names[] = $row['phar_name'];
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
    $stmt = $mysqli->prepare("SELECT * FROM his_patients WHERE pat_number=?");
    $stmt->bind_param('s', $pat_number);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_object()) {
    ?>

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <h4 class="page-title">Add Patient Prescription</h4>

                <!-- Form -->
                <form method="post">
                    <input type="hidden" name="pres_pat_number" value="<?= htmlspecialchars($row->pat_number); ?>">
                    <div class="form-group">
                        <label>Prescriptions</label>
                        <div class="row font-weight-bold text-secondary mb-2">
                            <div class="col-3">Medicine</div>
                            <div class="col-2">Dosage</div>
                            <div class="col-2">Pattern</div>
                            <div class="col-1">Days</div>
                            <div class="col-1">Action</div>
                        </div>
                        <div id="prescriptions_display"></div>
                        <button type="button" class="btn btn-outline-success mt-2" data-toggle="modal" data-target="#prescriptionModal">
                            <i class="fa fa-plus"></i> Add Prescription
                        </button>
                    </div>

                    <div id="hidden-fields"></div>
                    <button type="submit" name="add_patient_presc" class="btn btn-primary mt-3">Submit Prescription</button>
                </form>
            </div>
        </div>
        <?php } ?>
        <?php include('assets/inc/footer.php'); ?>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="prescriptionModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Prescription</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
            <label>Medicine</label>
            <input type="text" id="modal-medicine" class="form-control" list="med-suggestions" autocomplete="off" required>
            <datalist id="med-suggestions">
                <?php foreach ($med_names as $name): ?>
                    <option value="<?= htmlspecialchars($name); ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        <div class="form-row">
            <div class="form-group col">
                <label>Dosage Value</label>
                <input type="number" id="modal-unit-value" class="form-control" min="0.01" step="0.01" required>
            </div>
            <div class="form-group col">
                <label>Unit</label>
                <select id="modal-unit-type" class="form-control" required>
                    <option value="">Select Unit</option>
                    <option value="tablet">Tablet</option>
                    <option value="ml">ml</option>
                    <option value="g">g</option>
                    <option value="capsule">Capsule</option>
                    <option value="bottle">Bottle</option>
                    <option value="ampoule">Ampoule</option>
                    <option value="drop">Drop</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col">
                <label>Dose per time</label>
                <input type="number" id="modal-dose-per-time" class="form-control" required>
            </div>
            <div class="form-group col">
                <label>Times per day</label>
                <input type="number" id="modal-times-per-day" class="form-control" required>
            </div>
        </div>
        <div class="form-group">
            <label>Duration (in days)</label>
            <input type="number" id="modal-duration" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="add-to-list">Add</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="assets/js/vendor.min.js"></script>
<script src="assets/js/app.min.js"></script>
<script>
$(document).ready(function () {
    let index = 0;

    $('#add-to-list').click(function () {
        const med = $('#modal-medicine').val().trim();
        const val = $('#modal-unit-value').val().trim();
        const unit = $('#modal-unit-type').val();
        const dose = $('#modal-dose-per-time').val().trim();
        const freq = $('#modal-times-per-day').val().trim();
        const dur = $('#modal-duration').val().trim();

        if (!med || !val || !unit || !dose || !freq || !dur) {
            alert('All fields are required.');
            return;
        }

        const dosage = `${val} ${unit}`;
        const pattern = `${dose}*${freq}`;

        $('#prescriptions_display').append(`
            <div class="row mb-2 align-items-center" id="prescription-${index}">
                <div class="col-3">${med}</div>
                <div class="col-2">${dosage}</div>
                <div class="col-2">${pattern}</div>
                <div class="col-1">${dur}</div>
                <div class="col-1"><button class="btn btn-sm btn-danger remove" data-id="${index}">X</button></div>
            </div>
        `);

        $('#hidden-fields').append(`
            <input type="hidden" name="medicine_name[]" value="${med}" id="med-${index}">
            <input type="hidden" name="dosage[]" value="${dosage}" id="dosage-${index}">
            <input type="hidden" name="pattern[]" value="${pattern}" id="pattern-${index}">
            <input type="hidden" name="duration[]" value="${dur}" id="duration-${index}">
        `);

        index++;
        $('#prescriptionModal').modal('hide');
        $('#modal-medicine, #modal-unit-value, #modal-unit-type, #modal-dose-per-time, #modal-times-per-day, #modal-duration').val('');
    });

    $(document).on('click', '.remove', function () {
        const id = $(this).data('id');
        $(`#prescription-${id}`).remove();
        $(`#med-${id}, #dosage-${id}, #pattern-${id}, #duration-${id}`).remove();
    });
});
</script>
</body>
</html>
