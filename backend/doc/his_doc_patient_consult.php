<?php
    session_start();
    include('assets/inc/config.php');
    include('assets/inc/checklogin.php');
    check_login();
    $doc_id = $_SESSION['doc_id'];
?>

<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php');?>

<body>
    <!-- Begin page -->
    <div id="wrapper">

        <!-- Topbar Start -->
        <?php include('assets/inc/nav.php');?>
        <!-- end Topbar -->

        <!-- ========== Left Sidebar Start ========== -->
        <?php include("assets/inc/sidebar.php");?>
        <!-- Left Sidebar End -->

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Laboratory</a></li>
                                        <li class="breadcrumb-item active">Add Patient's Vitals</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Doctor Panel</h4>
                            </div>
                        </div>
                    </div>     
                    <!-- end page title --> 

                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <h4 class="header-title"></h4>
                                <div class="mb-2">
                                    <div class="row">
                                        <div class="col-12 text-sm-center form-inline" >
                                            <div class="form-group mr-2" style="display:none">
                                                <select id="demo-foo-filter-status" class="custom-select custom-select-sm">
                                                    <option value="">Show all</option>
                                                    <option value="Discharged">Discharged</option>
                                                    <option value="OutPatients">OutPatients</option>
                                                    <option value="InPatients">InPatients</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <input id="demo-foo-search" type="text" placeholder="Search" class="form-control form-control-sm" autocomplete="on">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table id="demo-foo-filtering" class="table table-bordered toggle-circle mb-0" data-page-size="7">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th data-toggle="true">Patient Name</th>
                                                <th data-hide="phone">Patient Age</th>
                                                <th data-hide="phone">Body Temp</th>
                                                <th data-hide="phone">Heart Pulse</th>
                                                <th data-hide="phone">Blood Press</th>
                                                <th data-hide="phone">Resparate</th>
                                                <th data-hide="phone">Date</th>
                                                <th data-hide="phone">Action</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                                $ret = "SELECT 
                                                            p.pat_fname, 
                                                            p.pat_lname, 
                                                            p.pat_age,
                                                            p.pat_number,
                                                            v.vit_bodytemp, 
                                                            v.vit_heartpulse, 
                                                            v.vit_bloodpress, 
                                                            v.vit_resprate, 
                                                            v.vit_daterec
                                                        FROM his_vitals v
                                                        JOIN his_patients p ON p.pat_number = v.vit_pat_number
                                                        ORDER BY RAND()";
                                                $stmt = $mysqli->prepare($ret);
                                                $stmt->execute();
                                                $res = $stmt->get_result();
                                                $cnt = 1;

                                                while($row = $res->fetch_object()) {
                                            ?>
                                                <tr>
                                                    <td><?php echo $cnt; ?></td>
                                                    <td><?php echo $row->pat_fname . ' ' . $row->pat_lname; ?></td>
                                                    <td><?php echo $row->pat_age; ?> Years</td>
                                                    <td><?php echo $row->vit_bodytemp; ?></td>
                                                    <td><?php echo $row->vit_heartpulse; ?></td>
                                                    <td><?php echo $row->vit_bloodpress; ?></td>
                                                    <td><?php echo $row->vit_resprate; ?></td>
                                                    <td><?php echo $row->vit_daterec; ?></td>
                                                    <td>
                                                        <a href="his_doc_vital_consult.php?pat_number=<?php echo $row->pat_number; ?>" class="badge badge-success">
                                                            <i class="mdi mdi-beaker"></i> Capture Vitals
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php $cnt++; } ?>
                                        </tbody>

                                        <tfoot>
                                            <tr class="active">
                                                <td colspan="9">
                                                    <div class="text-right">
                                                        <ul class="pagination pagination-rounded justify-content-end footable-pagination m-t-10 mb-0"></ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div> <!-- end .table-responsive-->
                            </div> <!-- end card-box -->
                        </div> <!-- end col -->
                    </div>
                    <!-- end row -->

                </div> <!-- container -->

            </div> <!-- content -->

            <!-- Footer Start -->
            <?php include('assets/inc/footer.php');?>
            <!-- end Footer -->

        </div>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>

    <!-- Footable js -->
    <script src="assets/libs/footable/footable.all.min.js"></script>

    <!-- Init js -->
    <script src="assets/js/pages/foo-tables.init.js"></script>

    <!-- App js -->
    <script src="assets/js/app.min.js"></script>
    
</body>
</html>
