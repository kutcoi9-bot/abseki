<?PHP 
require_once'../sw-library/sw-config.php';
require_once'../sw-library/sw-function.php';
//require_once'../sw-library/csrf.php';

$ip_login 		    = $_SERVER['REMOTE_ADDR'];
$created_login	    = date('Y-m-d H:i:s');
$iB 			    = getBrowser();
$browser 		    = $iB['name'].' '.$iB['version'];
$expired_cookie     = time()+60*60*24*7;

// ==========================================================
// KONFIGURASI GRUP WA & JUMLAH SISWA PER NOTIFIKASI
// ==========================================================
// 1. Masukkan ID Grup WhatsApp berdasarkan ID Kelas (dari database)
$grup_wa_kelas = array(
    '1' => '120363000000000001@g.us', // ID Kelas 1
    '2' => '120363000000000002@g.us', // ID Kelas 2
    '3' => '120363000000000003@g.us'  // ID Kelas 3, dst..
);

// 2. Tentukan berapa jumlah siswa per-1 kali pengiriman pesan
// Jika rata-rata 1 kelas 30 siswa, angka 10 akan membuat notif terkirim 3 kali.
$batch_size = 10; 
// ==========================================================


switch (@$_GET['action']){
    
// ==========================================================
// 1. PROSES ABSEN MASUK
// ==========================================================
case 'absen-in':
$error = array();

if (empty($_POST['qrcode'])) {
  $error[] = 'Barcode/Qr Code tidak boleh kosong';
} else {
  $qrcode = anti_injection($_POST['qrcode']);
}

if (empty($error)){

  // TAMBAHAN: Memastikan user.kelas ikut dipanggil di query select
  $query_siswa ="SELECT user.user_id,user.nama_lengkap,user.telp,user.kelas,kelas.nama_kelas,user.avatar FROM user
  INNER JOIN kelas ON user.kelas = kelas.kelas_id WHERE user.nisn='$qrcode'";
  $result_siswa = $connection->query($query_siswa);
  
  if ($result_siswa->num_rows > 0){
      $data_siswa = $result_siswa->fetch_assoc();

      $query_waktu = "SELECT jam_masuk,jam_telat,jam_pulang FROM waktu WHERE hari='$hari_ini' AND active='Y'";
      $result_waktu = $connection->query($query_waktu);
      if($result_waktu->num_rows > 0){
        $data_waktu = $result_waktu->fetch_assoc();

        if($data_waktu['jam_telat'] > $time){
          $status_masuk ='Tepat Waktu';
        }else{
          $status_masuk ='Telat';
        }

        $query_absen ="SELECT absen_id FROM absen WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
        $result_absen = $connection->query($query_absen);
        
        if(!$result_absen->num_rows > 0) {

                /** Jika belum ada maka tambah absen baru */
                $add ="INSERT INTO absen (user_id,
                        tanggal,
                        jam_masuk,
                        jam_toleransi,
                        jam_pulang,
                        absen_in,
                        absen_out,
                        status_masuk,
                        status_pulang,
                        map_in,
                        map_out,
                        kehadiran,
                        keterangan) values('$data_siswa[user_id]',
                        '$date',
                        '$data_waktu[jam_masuk]',
                        '$data_waktu[jam_telat]',
                        '$data_waktu[jam_pulang]',
                        '$time_absen',
                        '00:00:00', /** Jam Pulang kosong */
                        '$status_masuk',
                        '', /** Status Pulang kosong */
                        '',
                        '', /** Latitude out */
                        'Hadir', /** 1. Hadir */
                        '-')"; /** Keterangan Kosong */
                  
                if($connection->query($add) === false) { 
                    echo'Sepertinya Sistem Kami sedang error!';
                    die($connection->error.__LINE__); 
                } else{
                    echo'success/Terimakasih "'.$data_siswa['nama_lengkap'].'", Absensi Anda berhasil pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.' Selamat Belajar!';
                    
                    // ====================================================
                    // LOGIK TRIGGER WA REAL-TIME BATCHING (ABSEN MASUK)
                    // ====================================================
                    $id_kelas = $data_siswa['kelas'];
                    $nama_kelas = $data_siswa['nama_kelas'];

                    if(array_key_exists($id_kelas, $grup_wa_kelas)) {
                        $id_grup = $grup_wa_kelas[$id_kelas];
                        
                        // Cek Total Siswa Kelas Ini & Total yang sudah Absen Masuk hari ini
                        $q_hadir = "SELECT COUNT(absen_id) AS total_hadir FROM absen INNER JOIN user ON absen.user_id=user.user_id WHERE user.kelas='$id_kelas' AND absen.tanggal='$date' AND absen.absen_in != '00:00:00'";
                        $total_hadir = $connection->query($q_hadir)->fetch_assoc()['total_hadir'];
                        
                        $q_total = "SELECT COUNT(user_id) AS total_siswa FROM user WHERE kelas='$id_kelas'";
                        $total_siswa = $connection->query($q_total)->fetch_assoc()['total_siswa'];

                        $is_trigger = false;
                        $offset = 0;
                        $limit = $batch_size;
                        $batch_ke = "";

                        // Trigger jika menyentuh kelipatan 10 (contoh: 10, 20, 30)
                        if ($total_hadir > 0 && $total_hadir % $batch_size == 0) {
                            $is_trigger = true;
                            $offset = $total_hadir - $batch_size;
                            $batch_ke = ($total_hadir / $batch_size);
                        } 
                        // Trigger paksa jika ini adalah siswa terakhir dari total seluruh kelas
                        elseif ($total_hadir == $total_siswa) {
                            $sisa = $total_siswa % $batch_size;
                            if ($sisa > 0) {
                                $is_trigger = true;
                                $offset = $total_hadir - $sisa;
                                $limit = $sisa;
                                $batch_ke = "Akhir";
                            }
                        }

                        // JIKA TER-TRIGGER, KIRIM PESAN
                        if ($is_trigger) {
                            // Ambil list data siswa khusus untuk porsi/batch ini saja
                            $q_list = "SELECT user.nama_lengkap, absen.absen_in, absen.status_masuk FROM absen INNER JOIN user ON absen.user_id=user.user_id WHERE user.kelas='$id_kelas' AND absen.tanggal='$date' AND absen.absen_in != '00:00:00' ORDER BY absen.absen_in ASC LIMIT $offset, $limit";
                            $res_list = $connection->query($q_list);

                            $isipesan  = "*LAPORAN ABSEN MASUK (Bagian $batch_ke)*%0A";
                            $isipesan .= "Kelas : *$nama_kelas*%0A";
                            $isipesan .= "Tanggal : ".tanggal_ind($date)."%0A%0A";
                            
                            $no = 1;
                            while($row = $res_list->fetch_assoc()) {
                                $isipesan .= $no.". ".$row['nama_lengkap']." (".$row['absen_in']." - ".$row['status_masuk'].")%0A";
                                $no++;
                            }
                            $isipesan .= "%0ATerima Kasih.";
                            $isipesan = str_replace(' ', '%20', $isipesan);
                            
                            if($whatsapp_tipe == 'wablas'){
                                $pesan_wablas = str_replace('%0A', '<br>', str_replace('%20', ' ', $isipesan));
                                KirimWa($id_grup, $pesan_wablas, $link, $token);
                            }
                            if($whatsapp_tipe == 'universal'){
                                KirimWa($sender, $id_grup, $isipesan, $link, $token);
                            }
                        }
                    }
                    // ====================================================
                }
              }else{
                echo'success/Terimakasih "'.$data_siswa['nama_lengkap'].'", Absensi Anda berhasil pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.' Selamat Belajar!';
              }

            }else{
              echo'Hari ini tidak Ada jadwal/jam sekolah!';
            }

        }else{
          echo'Qr code/User tidak ditemukan, silahkan hubungi Admin!';
        }
    }else{       
      foreach ($error as $key => $values) {            
          echo"$values\n";
      }
  }

break;

// ==========================================================
// 2. PROSES ABSEN PULANG
// ==========================================================
case 'absen-out':

$error = array();

if (empty($_POST['qrcode'])) {
  $error[] = 'Barcode/Qr Code tidak boleh kosong';
} else {
  $qrcode= anti_injection($_POST['qrcode']);
}

if (empty($error)){
  // TAMBAHAN: Memastikan user.kelas ikut dipanggil di query select
  $query_siswa ="SELECT user.user_id,user.nama_lengkap,user.telp,user.kelas,kelas.nama_kelas,user.avatar FROM user
  INNER JOIN kelas ON user.kelas = kelas.kelas_id WHERE user.nisn='$qrcode'";
  $result_siswa = $connection->query($query_siswa);
  if ($result_siswa->num_rows > 0){
      $data_siswa = $result_siswa->fetch_assoc();

      $query_waktu = "SELECT jam_masuk,jam_telat,jam_pulang FROM waktu WHERE hari='$hari_ini' AND active='Y'";
      $result_waktu = $connection->query($query_waktu);
      if($result_waktu->num_rows > 0){
        $data_waktu = $result_waktu->fetch_assoc();
  
          if($data_waktu['jam_telat'] > $time){
            $status_pulang ='Pulang Cepat';
          }else{
            $status_pulang ='Tepat Waktu';
          }

          $query_absen ="SELECT absen_id,absen_out FROM absen WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
          $result_absen = $connection->query($query_absen);
          if($result_absen->num_rows > 0) {
            $data_absensi = $result_absen->fetch_assoc();

            if($data_absensi['absen_out']=='00:00:00'){
              
              $update ="UPDATE absen SET absen_out='$time_absen',
                      status_pulang='$status_pulang' WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
              if($connection->query($update) === false) { 
                echo'Sepertinya Sistem Kami sedang error!';
                die($connection->error.__LINE__); 
              } else{
                  echo'success/Selamat Anda berhasil Absen Pulang pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time.', Sampai ketemu besok "'.$data_siswa['nama_lengkap'].'"!';
                  
                  // ====================================================
                  // LOGIK TRIGGER WA REAL-TIME BATCHING (ABSEN PULANG)
                  // ====================================================
                  $id_kelas = $data_siswa['kelas'];
                  $nama_kelas = $data_siswa['nama_kelas'];

                  if(array_key_exists($id_kelas, $grup_wa_kelas)) {
                      $id_grup = $grup_wa_kelas[$id_kelas];
                      
                      // Cek Total Siswa Kelas Ini & Total yang sudah Absen Pulang hari ini
                      $q_pulang = "SELECT COUNT(absen_id) AS total_pulang FROM absen INNER JOIN user ON absen.user_id=user.user_id WHERE user.kelas='$id_kelas' AND absen.tanggal='$date' AND absen.absen_out != '00:00:00'";
                      $total_pulang = $connection->query($q_pulang)->fetch_assoc()['total_pulang'];
                      
                      $q_total = "SELECT COUNT(user_id) AS total_siswa FROM user WHERE kelas='$id_kelas'";
                      $total_siswa = $connection->query($q_total)->fetch_assoc()['total_siswa'];

                      $is_trigger = false;
                      $offset = 0;
                      $limit = $batch_size;
                      $batch_ke = "";

                      if ($total_pulang > 0 && $total_pulang % $batch_size == 0) {
                          $is_trigger = true;
                          $offset = $total_pulang - $batch_size;
                          $batch_ke = ($total_pulang / $batch_size);
                      } elseif ($total_pulang == $total_siswa) {
                          $sisa = $total_siswa % $batch_size;
                          if ($sisa > 0) {
                              $is_trigger = true;
                              $offset = $total_pulang - $sisa;
                              $limit = $sisa;
                              $batch_ke = "Akhir";
                          }
                      }

                      if ($is_trigger) {
                          $q_list = "SELECT user.nama_lengkap, absen.absen_out, absen.status_pulang FROM absen INNER JOIN user ON absen.user_id=user.user_id WHERE user.kelas='$id_kelas' AND absen.tanggal='$date' AND absen.absen_out != '00:00:00' ORDER BY absen.absen_out ASC LIMIT $offset, $limit";
                          $res_list = $connection->query($q_list);

                          $isipesan  = "*LAPORAN ABSEN PULANG (Bagian $batch_ke)*%0A";
                          $isipesan .= "Kelas : *$nama_kelas*%0A";
                          $isipesan .= "Tanggal : ".tanggal_ind($date)."%0A%0A";
                          
                          $no = 1;
                          while($row = $res_list->fetch_assoc()) {
                              $isipesan .= $no.". ".$row['nama_lengkap']." (".$row['absen_out']." - ".$row['status_pulang'].")%0A";
                              $no++;
                          }
                          $isipesan .= "%0ATerima Kasih.";
                          $isipesan = str_replace(' ', '%20', $isipesan);
                          
                          if($whatsapp_tipe == 'wablas'){
                              $pesan_wablas = str_replace('%0A', '<br>', str_replace('%20', ' ', $isipesan));
                              KirimWa($id_grup, $pesan_wablas, $link, $token);
                          }
                          if($whatsapp_tipe == 'universal'){
                              KirimWa($sender, $id_grup, $isipesan, $link, $token);
                          }
                      }
                  }
                  // ====================================================

              }
            }else{
              echo'success/Selamat Anda berhasil Absen Pulang pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time.', Sampai ketemu besok "'.$data_siswa['nama_lengkap'].'"!';
            }
          }else{
            echo'Sebelumnya Siswa '.$data_siswa['nama_lengkap'].' belum pernah Absen masuk!';
          }

        }else{
          echo'Hari ini tidak Ada jadwal/jam sekolah!';
        }

      }else{
        echo'Qr code/User tidak ditemukan, silahkan hubungi Admin!';
      }

    }else{
      foreach ($error as $key => $values) {            
        echo"$values\n";
      }
    }

break;

// ==========================================================
// 3. TAMPILAN DATA ABSENSI REALTIME DI LAYAR
// ==========================================================
case 'data-absensi':
$query_absen ="SELECT absen.tanggal,absen.absen_in,absen.absen_out,absen.status_masuk,absen.status_pulang,user.nama_lengkap,user.kelas,user.avatar FROM absen INNER JOIN user ON absen.user_id=user.user_id WHERE absen.tanggal='$date' ORDER BY absen.absen_id DESC";
$result_absen = $connection->query($query_absen);
if($result_absen->num_rows > 0){
    while($data_absen = $result_absen->fetch_assoc()){

      $query_kelas ="SELECT nama_kelas FROM kelas WHERE kelas_id='$data_absen[kelas]'";
      $result_kelas = $connection->query($query_kelas);
      $data_kelas = $result_kelas->fetch_assoc();

    if($data_absen['status_masuk']=='Telat'){
      $status='<span class="text-primary">Telat</span>';
    }
    elseif ($data_absen['status']='Tepat Waktu') {
      $status='<span class="text-primary">Tepat</span>';
    }
    else{
      $status='';
    }

    if(!file_exists('../sw-content/avatar/'.$data_absen['avatar'].'')){
      $avatar ='<img src="../sw-content/avatar/avatar.jpg" alt="img" class="image-block imaged w48">';
    }else{
      if($data_absen['avatar'] == ''){
          $avatar ='<img src="../sw-content/avatar.jpg" alt="img" class="image-block imaged w48">';
      }else{
          $avatar ='<img src="../sw-content/avatar/'.$data_absen['avatar'].'" alt="img" class="image-block imaged w48" heigt="48">';
      }
  }

  echo'<a href="#" class="item">
      <div class="detail">
          '.$avatar.'
          <div>
              <strong>'.strip_tags($data_absen['nama_lengkap']).' ['.$data_kelas['nama_kelas'].']</strong>
              <p>Masuk : '.$data_absen['absen_in'].' <br>Pulang : '.$data_absen['absen_out'].'</p>
          </div>
      </div>
  </a>';
  }
}else{
  echo'Saat ini belum ada data absensi terbaru';
}
break;

// ==========================================================
// 4. STATISTIK COUNTER
// ==========================================================
case'data-counter':
  $query_siswa = "SELECT user_id FROM user";
  $result_siswa = $connection->query($query_siswa);
  $jumlah_siswa = $result_siswa->num_rows;

  $query_absen_masuk ="SELECT absen_id FROM absen WHERE tanggal='$date' AND kehadiran='Hadir'";
  $result_absen_masuk = $connection->query($query_absen_masuk);
  $jumlah_absen_masuk = $result_absen_masuk->num_rows;
  $total_tidak_masuk = $jumlah_siswa - $jumlah_absen_masuk;

  $total_tidak_masuk_pesen = ($jumlah_siswa > 0) ? ($total_tidak_masuk/$jumlah_siswa * 100) : 0;

echo'<div class="col-md-6">
    <div class="stat-box bg-warning">
        <div class="title text-white">Masuk</div>
        <div class="value text-white">'.$result_absen_masuk->num_rows.'</div>

        <div class="progress mt-2">
            <div class="progress-bar" role="progressbar" style="width:'.number_format($jumlah_absen_masuk/$jumlah_siswa*100,0).'%;" aria-valuemin="0" aria-valuemax="100">'.number_format($jumlah_absen_masuk/$jumlah_siswa*100,0).'%</div>
        </div>
    </div>
</div>

<div class="col-md-6">
    <div class="stat-box bg-danger">
        <div class="title text-white">Tidak Masuk</div>
        <div class="value text-white">'.$total_tidak_masuk.'</div>

        <div class="progress mt-2">
            <div class="progress-bar" role="progressbar" style="width:'.number_format($total_tidak_masuk_pesen).'%;" aria-valuemin="0" aria-valuemax="100">'.number_format($total_tidak_masuk_pesen).'%</div>
        </div>
    </div>
</div>';
break;

}