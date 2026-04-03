<?PHP
require_once '../sw-library/sw-config.php';
require_once '../sw-library/sw-function.php';
//require_once'../sw-library/csrf.php';

$ip_login       = $_SERVER['REMOTE_ADDR'];
$created_login  = date('Y-m-d H:i:s');
$iB             = getBrowser();
$browser        = $iB['name'].' '.$iB['version'];
$expired_cookie = time()+60*60*24*7;

function buildMapsLink($latitude, $longitude){
  if ($latitude != '' && $longitude != '') {
    return 'https://www.google.com/maps?q=' . $latitude . ',' . $longitude;
  }
  return '';
}

function buildWhatsappMessage($type, $nama, $tanggal, $jam, $kelas, $status, $link_maps = ''){
  if($type == 'masuk'){
    $pesan = "Assalamualaikum Wr. Wb.\n\n";
    $pesan .= "Yth. Bapak/Ibu orang tua/wali dari ananda ".$nama.",\n\n";
    $pesan .= "Kami informasikan bahwa ananda telah hadir di sekolah.\n\n";
    $pesan .= "Detail kehadiran:\n";
    $pesan .= "Tanggal: ".$tanggal."\n";
    $pesan .= "Jam: ".$jam."\n";
    $pesan .= "Kelas: ".$kelas."\n";
    $pesan .= "Status: ".$status;
    if($link_maps != ''){
      $pesan .= "\nLokasi: ".$link_maps;
    }
    $pesan .= "\n\nTerima kasih.\n\nWassalamualaikum Wr. Wb.";
    return $pesan;
  }

  if($type == 'pulang'){
    $pesan = "Assalamualaikum Wr. Wb.\n\n";
    $pesan .= "Yth. Bapak/Ibu orang tua/wali dari ananda ".$nama.",\n\n";
    $pesan .= "Kami informasikan bahwa ananda telah melakukan absensi pulang.\n\n";
    $pesan .= "Detail kepulangan:\n";
    $pesan .= "Tanggal: ".$tanggal."\n";
    $pesan .= "Jam: ".$jam."\n";
    $pesan .= "Kelas: ".$kelas."\n";
    $pesan .= "Status: ".$status;
    if($link_maps != ''){
      $pesan .= "\nLokasi: ".$link_maps;
    }
    $pesan .= "\n\nTerima kasih.\n\nWassalamualaikum Wr. Wb.";
    return $pesan;
  }

  return '';
}

function formatWhatsappPayload($whatsapp_tipe, $pesan_text){
  if($whatsapp_tipe == 'wablas'){
    return nl2br($pesan_text);
  }

  if($whatsapp_tipe == 'universal'){
    return urlencode($pesan_text);
  }

  return $pesan_text;
}

switch (@$_GET['action']){

/* ================================
   ABSEN AUTO
   sebelum 10:00 = masuk
   setelah 10:00 = pulang
================================ */
case 'absen-auto':
$error = array();

if (empty($_POST['qrcode'])) {
  $error[] = 'Barcode/Qr Code tidak boleh kosong';
} else {
  $qrcode = anti_injection($_POST['qrcode']);
}

$latitude  = isset($_POST['latitude']) ? anti_injection($_POST['latitude']) : '';
$longitude = isset($_POST['longitude']) ? anti_injection($_POST['longitude']) : '';
$link_maps = buildMapsLink($latitude, $longitude);

if (empty($error)){

  $query_siswa = "SELECT user.user_id,user.nama_lengkap,user.telp,kelas.nama_kelas,user.avatar 
                  FROM user
                  INNER JOIN kelas ON user.kelas = kelas.kelas_id 
                  WHERE user.nisn='$qrcode'";
  $result_siswa = $connection->query($query_siswa);

  if ($result_siswa->num_rows > 0){
    $data_siswa = $result_siswa->fetch_assoc();
    $nomorwa    = $data_siswa['telp'];

    $query_waktu = "SELECT jam_masuk,jam_telat,jam_pulang FROM waktu WHERE hari='$hari_ini' AND active='Y'";
    $result_waktu = $connection->query($query_waktu);

    if($result_waktu->num_rows > 0){
      $data_waktu = $result_waktu->fetch_assoc();

      if($time < '10:00:00'){
        /* ===== ABSEN MASUK ===== */
        if($data_waktu['jam_telat'] > $time){
          $status_masuk = 'Tepat Waktu';
        }else{
          $status_masuk = 'Telat';
        }

        $query_absen = "SELECT absen_id FROM absen WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
        $result_absen = $connection->query($query_absen);

        if(!$result_absen->num_rows > 0){

          $pesan_text = buildWhatsappMessage(
            'masuk',
            $data_siswa['nama_lengkap'],
            tgl_ind($date),
            $time,
            $data_siswa['nama_kelas'],
            $status_masuk,
            $link_maps
          );
          $isipesan = formatWhatsappPayload($whatsapp_tipe, $pesan_text);

          $add = "INSERT INTO absen (
                    user_id,
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
                    keterangan
                  ) VALUES (
                    '$data_siswa[user_id]',
                    '$date',
                    '$data_waktu[jam_masuk]',
                    '$data_waktu[jam_telat]',
                    '$data_waktu[jam_pulang]',
                    '$time_absen',
                    '00:00:00',
                    '$status_masuk',
                    '',
                    '$link_maps',
                    '',
                    'Hadir',
                    '-'
                  )";

          if($connection->query($add) === false){
            echo 'Sepertinya Sistem Kami sedang error!';
            die($connection->error.__LINE__);
          } else{
            echo 'success/Terimakasih "'.$data_siswa['nama_lengkap'].'", Absensi masuk berhasil pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.' Selamat Belajar!';

            if($whatsapp_tipe == 'wablas'){
              KirimWa($nomorwa, $isipesan, $link, $token);
            }
            if($whatsapp_tipe == 'universal'){
              KirimWa($sender, $nomorwa, $isipesan, $link, $token);
            }
          }
        }else{
          echo 'success/Terimakasih "'.$data_siswa['nama_lengkap'].'", Absensi masuk sudah tercatat hari ini.';
        }

      }else{
        /* ===== ABSEN PULANG ===== */
        if($time < $data_waktu['jam_pulang']){
          $status_pulang = 'Pulang Cepat';
        }else{
          $status_pulang = 'Tepat Waktu';
        }

        $query_absen = "SELECT absen_id,absen_out FROM absen WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
        $result_absen = $connection->query($query_absen);

        if($result_absen->num_rows > 0){
          $data_absensi = $result_absen->fetch_assoc();

          if($data_absensi['absen_out'] == '00:00:00'){

            $update = "UPDATE absen 
                       SET absen_out='$time_absen',
                           status_pulang='$status_pulang',
                           map_out='$link_maps'
                       WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";

            if($connection->query($update) === false){
              echo 'Sepertinya Sistem Kami sedang error!';
              die($connection->error.__LINE__);
            } else{
              $pesan_text = buildWhatsappMessage(
                'pulang',
                $data_siswa['nama_lengkap'],
                tgl_ind($date),
                $time,
                $data_siswa['nama_kelas'],
                $status_pulang,
                $link_maps
              );
              $isipesan = formatWhatsappPayload($whatsapp_tipe, $pesan_text);

              echo 'success/Selamat Anda berhasil Absen Pulang pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time.', Sampai ketemu besok "'.$data_siswa['nama_lengkap'].'"!';

              if($whatsapp_tipe == 'wablas'){
                KirimWa($nomorwa, $isipesan, $link, $token);
              }
              if($whatsapp_tipe == 'universal'){
                KirimWa($sender, $nomorwa, $isipesan, $link, $token);
              }
            }

          }else{
            echo 'success/Absensi pulang "'.$data_siswa['nama_lengkap'].'" sudah tercatat hari ini.';
          }
        }else{
          echo 'Sebelumnya Siswa '.$data_siswa['nama_lengkap'].' belum pernah Absen masuk!';
        }
      }

    }else{
      echo 'Hari ini tidak Ada jadwal/jam sekolah!';
    }

  }else{
    echo 'Qr code/User tidak ditemukan, silahkan hubungi Admin!';
  }

}else{
  foreach ($error as $key => $values) {
    echo "$values\n";
  }
}
break;


/* ================================
   ABSEN MASUK MANUAL / LAMA
================================ */
case 'absen-in':
$error = array();

if (empty($_POST['qrcode'])) {
  $error[] = 'Barcode/Qr Code tidak boleh kosong';
} else {
  $qrcode = anti_injection($_POST['qrcode']);
}

$latitude  = isset($_POST['latitude']) ? anti_injection($_POST['latitude']) : '';
$longitude = isset($_POST['longitude']) ? anti_injection($_POST['longitude']) : '';
$link_maps = buildMapsLink($latitude, $longitude);

if (empty($error)){

  $query_siswa ="SELECT user.user_id,user.nama_lengkap,user.telp,kelas.nama_kelas,user.avatar FROM user
  INNER JOIN kelas ON user.kelas = kelas.kelas_id WHERE user.nisn='$qrcode'";
  $result_siswa = $connection->query($query_siswa);

  if ($result_siswa->num_rows > 0){
    $data_siswa = $result_siswa->fetch_assoc();
    $nomorwa    = $data_siswa['telp'];

    $query_waktu = "SELECT jam_masuk,jam_telat,jam_pulang FROM waktu WHERE hari='$hari_ini' AND active='Y'";
    $result_waktu = $connection->query($query_waktu);

    if($result_waktu->num_rows > 0){
      $data_waktu = $result_waktu->fetch_assoc();

      if($data_waktu['jam_telat'] > $time){
        $status_masuk ='Tepat Waktu';
      }else{
        $status_masuk ='Telat';
      }

      if($time < $data_waktu['jam_pulang']){
        $status_pulang ='Pulang Cepat';
      }else{
        $status_pulang ='Tepat Waktu';
      }

      $query_absen ="SELECT absen_id FROM absen WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
      $result_absen = $connection->query($query_absen);

      if(!$result_absen->num_rows > 0) {

        $pesan_text = buildWhatsappMessage(
          'masuk',
          $data_siswa['nama_lengkap'],
          tgl_ind($date),
          $time,
          $data_siswa['nama_kelas'],
          $status_masuk,
          $link_maps
        );
        $isipesan = formatWhatsappPayload($whatsapp_tipe, $pesan_text);

        $add ="INSERT INTO absen (
                  user_id,
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
                  keterangan
                ) values(
                  '$data_siswa[user_id]',
                  '$date',
                  '$data_waktu[jam_masuk]',
                  '$data_waktu[jam_telat]',
                  '$data_waktu[jam_pulang]',
                  '$time_absen',
                  '00:00:00',
                  '$status_masuk',
                  '',
                  '$link_maps',
                  '',
                  'Hadir',
                  '-'
                )";

        if($connection->query($add) === false) {
          echo 'Sepertinya Sistem Kami sedang error!';
          die($connection->error.__LINE__);
        } else{
          echo 'success/Terimakasih "'.$data_siswa['nama_lengkap'].'", Absensi Anda berhasil pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.' Selamat Belajar!';

          if($whatsapp_tipe == 'wablas'){
            KirimWa($nomorwa, $isipesan, $link, $token);
          }
          if($whatsapp_tipe == 'universal'){
            KirimWa($sender, $nomorwa, $isipesan, $link, $token);
          }
        }
      }else{
        echo 'success/Terimakasih "'.$data_siswa['nama_lengkap'].'", Absensi Anda berhasil pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.' Selamat Belajar!';
      }

    }else{
      echo 'Hari ini tidak Ada jadwal/jam sekolah!';
    }

  }else{
    echo 'Qr code/User tidak ditemukan, silahkan hubungi Admin!';
  }

}else{
  foreach ($error as $key => $values) {
    echo "$values\n";
  }
}
break;


/* ================================
   ABSEN PULANG MANUAL / LAMA
================================ */
case 'absen-out':
$error = array();

if (empty($_POST['qrcode'])) {
  $error[] = 'Barcode/Qr Code tidak boleh kosong';
} else {
  $qrcode = anti_injection($_POST['qrcode']);
}

$latitude  = isset($_POST['latitude']) ? anti_injection($_POST['latitude']) : '';
$longitude = isset($_POST['longitude']) ? anti_injection($_POST['longitude']) : '';
$link_maps = buildMapsLink($latitude, $longitude);

if (empty($error)){
  $query_siswa ="SELECT user.user_id,user.nama_lengkap,user.telp,kelas.nama_kelas,user.avatar FROM user
  INNER JOIN kelas ON user.kelas = kelas.kelas_id WHERE user.nisn='$qrcode'";
  $result_siswa = $connection->query($query_siswa);

  if ($result_siswa->num_rows > 0){
    $data_siswa = $result_siswa->fetch_assoc();
    $nomorwa    = $data_siswa['telp'];

    $query_waktu = "SELECT jam_masuk,jam_telat,jam_pulang FROM waktu WHERE hari='$hari_ini' AND active='Y'";
    $result_waktu = $connection->query($query_waktu);

    if($result_waktu->num_rows > 0){
      $data_waktu = $result_waktu->fetch_assoc();

      if($time < $data_waktu['jam_pulang']){
        $status_pulang ='Pulang Cepat';
      }else{
        $status_pulang ='Tepat Waktu';
      }

      $pesan_text = buildWhatsappMessage(
        'pulang',
        $data_siswa['nama_lengkap'],
        tgl_ind($date),
        $time,
        $data_siswa['nama_kelas'],
        $status_pulang,
        $link_maps
      );
      $isipesan = formatWhatsappPayload($whatsapp_tipe, $pesan_text);

      $query_absen ="SELECT absen_id,absen_out FROM absen WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
      $result_absen = $connection->query($query_absen);

      if($result_absen->num_rows > 0) {
        $data_absensi = $result_absen->fetch_assoc();

        if($data_absensi['absen_out']=='00:00:00'){
          $update ="UPDATE absen SET 
                      absen_out='$time_absen',
                      status_pulang='$status_pulang',
                      map_out='$link_maps'
                    WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";

          if($connection->query($update) === false) {
            echo 'Sepertinya Sistem Kami sedang error!';
            die($connection->error.__LINE__);
          } else{
            echo 'success/Selamat Anda berhasil Absen Pulang pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time.', Sampai ketemu besok "'.$data_siswa['nama_lengkap'].'"!';

            if($whatsapp_tipe == 'wablas'){
              KirimWa($nomorwa, $isipesan, $link, $token);
            }
            if($whatsapp_tipe == 'universal'){
              KirimWa($sender, $nomorwa, $isipesan, $link, $token);
            }
          }
        }else{
          echo 'success/Selamat Anda berhasil Absen Pulang pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time.', Sampai ketemu besok "'.$data_siswa['nama_lengkap'].'"!';
        }

      }else{
        echo 'Sebelumnya Siswa '.$data_siswa['nama_lengkap'].' belum pernah Absen masuk!';
      }

    }else{
      echo 'Hari ini tidak Ada jadwal/jam sekolah!';
    }

  }else{
    echo 'Qr code/User tidak ditemukan, silahkan hubungi Admin!';
  }

}else{
  foreach ($error as $key => $values) {
    echo "$values\n";
  }
}
break;


/* ================================
   DATA ABSENSI
================================ */
case 'data-absensi':
$query_absen ="SELECT absen.tanggal,absen.absen_in,absen.absen_out,absen.status_masuk,absen.status_pulang,user.nama_lengkap,user.kelas,user.avatar 
               FROM absen 
               INNER JOIN user ON absen.user_id=user.user_id 
               WHERE absen.tanggal='$date' 
               ORDER BY absen.absen_id DESC";
$result_absen = $connection->query($query_absen);

if($result_absen->num_rows > 0){
  while($data_absen = $result_absen->fetch_assoc()){

    $query_kelas ="SELECT nama_kelas FROM kelas WHERE kelas_id='$data_absen[kelas]'";
    $result_kelas = $connection->query($query_kelas);
    $data_kelas = $result_kelas->fetch_assoc();

    if($data_absen['status_masuk'] == 'Telat'){
      $status = '<span class="text-primary">Telat</span>';
    }
    elseif($data_absen['status_masuk'] == 'Tepat Waktu'){
      $status = '<span class="text-primary">Tepat</span>';
    }
    else{
      $status = '';
    }

    if(!file_exists('../sw-content/avatar/'.$data_absen['avatar'].'')){
      $avatar = '<img src="../sw-content/avatar/avatar.jpg" alt="img" class="image-block imaged w48">';
    }else{
      if($data_absen['avatar'] == ''){
        $avatar = '<img src="../sw-content/avatar.jpg" alt="img" class="image-block imaged w48">';
      }else{
        $avatar = '<img src="../sw-content/avatar/'.$data_absen['avatar'].'" alt="img" class="image-block imaged w48" heigt="48">';
      }
    }

    echo '<a href="#" class="item">
      <div class="detail">
        '.$avatar.'
        <div>
          <strong>'.strip_tags($data_absen['nama_lengkap']).' ['.$data_kelas['nama_kelas'].']</strong>
          <p>Masuk : '.$data_absen['absen_in'].' <br>Pulang : '.$data_absen['absen_out'].'<br>'.$status.'</p>
        </div>
      </div>
    </a>';
  }
}else{
  echo 'Saat ini belum ada data absensi terbaru';
}
break;


/* ================================
   DATA COUNTER
================================ */
case 'data-counter':
$query_siswa = "SELECT user_id FROM user";
$result_siswa = $connection->query($query_siswa);
$jumlah_siswa = $result_siswa->num_rows;

$query_absen_masuk ="SELECT absen_id FROM absen WHERE tanggal='$date' AND kehadiran='Hadir'";
$result_absen_masuk = $connection->query($query_absen_masuk);
$jumlah_absen_masuk = $result_absen_masuk->num_rows;

$total_tidak_masuk = $jumlah_siswa - $jumlah_absen_masuk;
$total_tidak_masuk_pesen = ($jumlah_siswa > 0) ? ($total_tidak_masuk / $jumlah_siswa * 100) : 0;
$persen_masuk = ($jumlah_siswa > 0) ? ($jumlah_absen_masuk / $jumlah_siswa * 100) : 0;

echo '<div class="col-md-6">
  <div class="stat-box bg-warning">
    <div class="title text-white">Masuk</div>
    <div class="value text-white">'.$jumlah_absen_masuk.'</div>

    <div class="progress mt-2">
      <div class="progress-bar" role="progressbar" style="width:'.number_format($persen_masuk,0).'%;" aria-valuemin="0" aria-valuemax="100">'.number_format($persen_masuk,0).'%</div>
    </div>
  </div>
</div>

<div class="col-md-6">
  <div class="stat-box bg-danger">
    <div class="title text-white">Tidak Masuk</div>
    <div class="value text-white">'.$total_tidak_masuk.'</div>

    <div class="progress mt-2">
      <div class="progress-bar" role="progressbar" style="width:'.number_format($total_tidak_masuk_pesen,0).'%;" aria-valuemin="0" aria-valuemax="100">'.number_format($total_tidak_masuk_pesen,0).'%</div>
    </div>
  </div>
</div>';
break;

}
?>