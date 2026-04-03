<?php 
// FILE: /scan-barcode/index.php
// Halaman scan menggunakan Barcode Scanner (USB)

include_once '../sw-library/sw-config.php';
include_once '../sw-library/sw-function.php';

// Cek sesi login admin/petugas jika perlu
// if(empty($_SESSION['user'])){ header('location:../login'); exit;}

$query_jadwal = "SELECT jam_masuk, jam_pulang FROM waktu WHERE hari='$hari_ini' AND active='Y'";
$result_jadwal = $connection->query($query_jadwal); 
$info_jadwal = ($result_jadwal->num_rows > 0) ? $result_jadwal->fetch_assoc() : null;

echo '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Scan Barcode - '.strip_tags($site_name).'</title>
    <link rel="icon" href="../sw-content/'.$site_favicon.'" type="image/png">
    
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="../module/sw-assets/css/style.css">
    <link rel="stylesheet" href="../module/sw-assets/css/sw-custom.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700">
    
    <style>
        body { background-color: #f0f2f5; }
        .qrcode {
            padding: 15px 20px!important;
            height: 60px!important;
            font-size: 24px!important;
            font-weight: bold;
            text-align: center;
            letter-spacing: 2px;
            border: 2px solid #5A3DE6;
            border-radius: 10px;
            color: #333!important;
            background: #fff!important;
        }
        .qrcode:focus {
            box-shadow: 0 0 0 5px rgba(90, 61, 230, 0.2);
            border-color: #4318FF;
        }
        .qrcode::placeholder {
            color: #ccc!important;
            font-weight: normal;
            font-size: 18px;
            letter-spacing: normal;
        }
        
        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .appHeader { background: #5A3DE6; color:white; border-bottom:none; }
        
        /* Loading Overlay */
        .submit-loading {
            position: absolute; right: 25px; top: 18px;
            display: none;
        }
    </style>
</head>

<body>

<!-- Header -->
<div class="appHeader">
    <div class="pageTitle">
        <a href="../" style="color:white; text-decoration:none;">
            <img src="../sw-content/'.$site_logo.'" alt="logo" style="height:30px; background:white; padding:2px; border-radius:4px;">
            <span style="margin-left:10px; font-weight:bold;">SCANNER BARCODE</span>
        </a>
    </div>
    <div class="right">
        <div class="headerButton">
            <span class="clock" style="font-weight:bold; font-size:16px;"></span>
        </div>
    </div>
</div>

<!-- Content -->
<div id="appCapsule" style="padding-top: 70px;">
    <div class="container-fluid">
        <div class="row">
            
            <!-- Kolom Kiri: Info & Input -->
            <div class="col-md-5">
                
                <!-- Info Jadwal -->
                <div class="card card-custom">
                    <div class="card-body text-center">
                        <h5 class="text-muted mb-1">'.format_hari_tanggal($date).'</h5>
                        <h3 class="clock text-primary" style="font-weight:bold; font-size:32px; margin:0;"></h3>
                        <div class="mt-3 badge badge-light p-2" style="font-size:14px; width:100%">';
                            if($info_jadwal){
                                echo 'Jadwal: '.$info_jadwal['jam_masuk'].' - '.$info_jadwal['jam_pulang'];
                            } else {
                                echo '<span class="text-danger">Hari ini Libur</span>';
                            }
                        echo '
                        </div>
                    </div>
                </div>

                <!-- Input Scan -->
                <div class="card card-custom bg-white">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <img src="../module/sw-assets/img/barcode-scanner.png" alt="scan" style="width:80px; opacity:0.8; margin-bottom:10px;">
                            <h4 style="font-weight:700">Scan Kartu / QR Code</h4>
                            <p class="text-muted" style="font-size:13px;">
                                Pastikan kursor aktif di kotak input.<br>
                                <span class="geo-status text-warning">Menunggu Lokasi...</span>
                            </p>
                        </div>

                        <form action="javascript:void(0);" class="form-absen" method="POST">
                            <div class="form-group relative">
                                <input type="text" name="qrcode" class="form-control qrcode" placeholder="Klik disini & Scan..." autocomplete="off" autofocus required>
                                <div class="submit-loading">
                                    <span class="spinner-border spinner-border-sm text-primary"></span>
                                </div>
                            </div>
                            <!-- Tombol hidden untuk trigger enter -->
                            <button type="submit" class="d-none button-submit"></button>
                        </form>
                        
                        <div class="alert alert-secondary mt-3" style="font-size:12px;">
                            <i class="fas fa-info-circle"></i> <b>Mode Otomatis:</b><br>
                            Sebelum 10:00 = <b>Masuk</b><br>
                            Setelah 10:00 = <b>Pulang</b>
                        </div>
                    </div>
                </div>

                <!-- Statistik -->
                <div class="row data-counter">
                    <!-- Load via AJAX -->
                </div>

            </div>

            <!-- Kolom Kanan: Riwayat -->
            <div class="col-md-7">
                <div class="card card-custom">
                    <div class="card-header bg-white" style="border-bottom:1px solid #eee;">
                        <h5 class="card-title m-0">Riwayat Scan Hari Ini</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="data-absensi" style="height: 500px; overflow-y: auto; padding:10px;">
                            <!-- Load via AJAX -->
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Konfigurasi PHP ke JS -->
<input type="hidden" id="site_url" value="./sw-proses.php">

<!-- Scripts -->
<script src="../module/sw-assets/js/lib/jquery-3.4.1.min.js"></script>
<script src="../module/sw-assets/js/lib/popper.min.js"></script>
<script src="../module/sw-assets/js/lib/bootstrap.min.js"></script>
<script src="../module/sw-assets/js/sweetalert.min.js"></script>
<script src="https://kit.fontawesome.com/0ccb04165b.js" crossorigin="anonymous"></script>

<!-- Script Utama Scan -->
<script src="sw-script.js"></script>

</body>
</html>';
?>