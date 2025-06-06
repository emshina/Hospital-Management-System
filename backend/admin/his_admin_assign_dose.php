<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$doc_id = $_SESSION['ad_id'];


if (isset($_GET['delete_dosage'])) {
    $id = intval($_GET['delete_dosage']);

    // Delete dosage records
    $stock_del = "DELETE FROM his_pharma_dosage WHERE phar_id = ?";
    $stmt1 = $mysqli->prepare($stock_del);
    $stmt1->bind_param('i', $id);

    if ($stmt1->execute()) {
        $success = "Dosage record deleted successfully.";
    } else {
        $err = "Error: Could not delete dosage. Try again.";
    }
    $stmt1->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<?php include('assets/inc/head.php'); ?>

<!-- FooTable CSS -->
<link href="assets/libs/footable/footable.core.min.css" rel="stylesheet" type="text/css" />

<body>
    <div id="wrapper">
        <?php include('assets/inc/nav.php'); ?>
        <?php include("assets/inc/sidebar.php"); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="javascript:void(0);">Pharmaceuticals</a></li>
                                        <li class="breadcrumb-item active">Medicine Dosage List</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Medicine Dosage List</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Search and Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">

                                <!-- Search box -->
                                <div class="mb-2">
                                    <div class="row">
                                        <div class="col-12 text-sm-center form-inline">
                                            <div class="form-group">
                                                <input id="demo-foo-search" type="text" placeholder="Search" class="form-control form-control-sm" autocomplete="on">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Table -->
                                <div class="table-responsive">
                                    <table class="table table-bordered toggle-circle mb-0" data-page-size="7" data-filter="#demo-foo-search">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Pharm Name</th>
                                                <th>Unit of Measure</th>
                                                <th>Dosage Pattern</th>
                                                <th>Duration (Days)</th>
                                                <th>Notes</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = "
                                                SELECT 
                                                    p.phar_id,
                                                    p.phar_name,
                                                    s.unit_of_measure,
                                                    d.dosage_pattern,
                                                    d.duration_days,
                                                    d.notes
                                                FROM 
                                                    his_pharmaceuticals p
                                                LEFT JOIN 
                                                    his_pharma_stock s ON p.phar_id = s.phar_id
                                                LEFT JOIN 
                                                    his_pharma_dosage d ON p.phar_id = d.phar_id AND s.unit_of_measure = d.unit_of_measure
                                                GROUP BY 
                                                    p.phar_name, s.unit_of_measure
                                                ORDER BY 
                                                    p.phar_name ASC
                                            ";
                                            $stmt = $mysqli->prepare($query);
                                            $stmt->execute();
                                            $res = $stmt->get_result();
                                            $cnt = 1;
                                            while ($row = $res->fetch_object()) {
                                            ?>
                                                <tr>
                                                    <td><?php echo $cnt++; ?></td>
                                                    <td><?php echo htmlspecialchars($row->phar_name); ?></td>
                                                    <td><?php echo htmlspecialchars($row->unit_of_measure); ?></td>
                                                    <td><?php echo htmlspecialchars($row->dosage_pattern ?: ''); ?></td>
                                                    <td><?php echo htmlspecialchars($row->duration_days ?: ''); ?></td>
                                                    <td><?php echo htmlspecialchars($row->notes ?: ''); ?></td>
                                                    <td>
                                                    <a href="his_admin_update_dosage.php?phar_id=<?php echo $row->phar_id; ?>&unit=<?php echo urlencode($row->unit_of_measure); ?>&phar_name=<?php echo urlencode($row->phar_name); ?>" class="badge badge-primary">
                                                        <i class="far fa-edit"></i> Edit
                                                    </a>
                                                    <a href="?delete_dosage=<?= $row->phar_id; ?>" class="badge badge-danger" onclick="return confirm('Delete this item?');">Delete</a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="7">
                                                    <div class="text-right">
                                                        <ul class="pagination pagination-rounded justify-content-end footable-pagination m-t-10 mb-0"></ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include('assets/inc/footer.php'); ?>
        </div>
    </div>

    <div class="rightbar-overlay"></div>

    <!-- JS Scripts -->
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/libs/footable/footable.all.min.js"></script>
    <script>
        jQuery(function($){
            $('.table').footable(); // Manual FooTable initialization
        });
    </script>
    <script src="assets/js/app.min.js"></script>

</body>
</html>
