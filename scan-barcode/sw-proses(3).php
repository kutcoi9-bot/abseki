<?PHP require_once'../sw-library/sw-config.php';
require_once'../sw-library/sw-function.php';
//require_once'../sw-library/csrf.php';

$ip_login 		    = $_SERVER['REMOTE_ADDR'];
$created_login	    = date('Y-m-d H:i:s');
$iB 			    = getBrowser();
$browser 		    = $iB['name'].' '.$iB['version'];
$expired_cookie     = time()+60*60*24*7;

// --- TAMBAHAN: Koordinat Lokasi Baru MTSN 1 Tana Toraja ---
$latitude_lokasi  = "-3.0880989";
$longitude_lokasi = "119.8502469";

// --- TAMBAHAN: Notifikasi Audio Sukses & Gagal (Perbaikan Path Absolut) ---
$audio_success = ' Terima Kasih.! <audio autoplay hidden><source src="/sw-content/audio/absen_berhasil.mp3" type="audio/mpeg"></audio>';
$audio_fail    = ' Ulangi Absensi.! <audio autoplay hidden><source src="/sw-content/audio/absen_gagal.mp3" type="audio/mpeg"></audio>';


switch (@$_GET['action']){
case 'absen-in':
$error = array();

if (empty($_POST['qrcode'])) {
  $error[] = 'Barcode/Qr Code tidak boleh kosong.'.$audio_fail;
} else {
  $qrcode = anti_injection($_POST['qrcode']);
}


if (empty($error)){

  $query_siswa ="SELECT user.user_id,user.nama_lengkap,user.telp,kelas.nama_kelas,user.avatar FROM user
  INNER JOIN kelas ON user.kelas = kelas.kelas_id WHERE user.nisn='$qrcode'";
  $result_siswa = $connection->query($query_siswa);
  if ($result_siswa->num_rows > 0){
      $data_siswa = $result_siswa->fetch_assoc();
      $nomorwa        = $data_siswa['telp'];

      $query_waktu = "SELECT jam_masuk,jam_telat,jam_pulang FROM waktu WHERE hari='$hari_ini' AND active='Y'";
      $result_waktu = $connection->query($query_waktu);
      if($result_waktu->num_rows > 0){
        $data_waktu = $result_waktu->fetch_assoc();

        if($data_waktu['jam_telat'] > $time){
          $status_masuk ='Tepat Waktu';
        }else{
          $status_masuk ='Telat';
        }

        if($data_waktu['jam_telat'] > $time){
          $status_pulang ='Pulang Cepat';
        }else{
          $status_pulang ='Tepat Waktu';
        }


        $query_absen ="SELECT absen_id FROM absen WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
        $result_absen = $connection->query($query_absen);
        if(!$result_absen->num_rows > 0) {

              
              if($whatsapp_tipe =='wablas'){
                $isipesan  = 'Assalamualaikum wr wb<br>Bapak/ibu siswa "'.$data_siswa['nama_lengkap'].'" telah masuk sekolah<br>Tanggal : '.tgl_ind($date).'<br>Jam : '.$time.'<br><br>Terimakasih';
              }
      
              if($whatsapp_tipe =='universal'){
                $isipesan  = 'Assalamualaikum wr wb%0ABapak/ibu siswa "'.$data_siswa['nama_lengkap'].'" telah masuk sekolah%0ATanggal : '.tgl_ind($date).'%0AJam : '.$time.'%0A%0ATerimakasih';
                $isipesan = str_replace(' ', '%20', $isipesan);
              }

                /** Jika belum ada makan tambah absen baru */
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
                    echo'Sepertinya Sistem Kami sedang error!'.$audio_fail;
                    die($connection->error.__LINE__); 
                } else{
                    echo'success/Terimakasih "'.$data_siswa['nama_lengkap'].'", Absensi Anda berhasil pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.' Selamat Belajar!'.$audio_success;
                    
                    if($whatsapp_tipe =='wablas'){
                      KirimWa($nomorwa,$isipesan,$link,$token);
                    }
                    if($whatsapp_tipe =='universal'){
                        KirimWa($sender,$nomorwa,$isipesan,$link,$token);
                    }
                }
              }else{
                /** Berikan notifikasi Absen masuk jika sudah terinput */
                echo'success/Terimakasih "'.$data_siswa['nama_lengkap'].'", Absensi Anda berhasil pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.' Selamat Belajar!'.$audio_success;
              }

            }else{
              echo'Hari ini tidak Ada jadwal/jam sekolah!'.$audio_fail;
            }

        }else{
          echo'Qr code/User tidak ditemukan, silahkan hubungi Admin!'.$audio_fail;
        }
    }else{       
      foreach ($error as $key => $values) {            
          echo"$values\n";
      }
  }

break;
case 'absen-out':

$error = array();

if (empty($_POST['qrcode'])) {
  $error[] = 'Barcode/Qr Code tidak boleh kosong.'.$audio_fail;
} else {
  $qrcode= anti_injection($_POST['qrcode']);
}

if (empty($error)){
  $query_siswa ="SELECT user.user_id,user.nama_lengkap,user.telp,kelas.nama_kelas,user.avatar FROM user
  INNER JOIN kelas ON user.kelas = kelas.kelas_id WHERE user.nisn='$qrcode'";
  $result_siswa = $connection->query($query_siswa);
  if ($result_siswa->num_rows > 0){
      $data_siswa = $result_siswa->fetch_assoc();
      $nomorwa        = $data_siswa['telp'];

      $query_waktu = "SELECT jam_masuk,jam_telat,jam_pulang FROM waktu WHERE hari='$hari_ini' AND active='Y'";
      $result_waktu = $connection->query($query_waktu);
      if($result_waktu->num_rows > 0){
        $data_waktu = $result_waktu->fetch_assoc();
  
          if($data_waktu['jam_telat'] > $time){
            $status_pulang ='Pulang Cepat';
          }else{
            $status_pulang ='Tepat Waktu';
          }

          
          if($whatsapp_tipe =='wablas'){
            $isipesan  = 'Assalamualaikum wr wb<br>Bapak/ibu siswa "'.$data_siswa['nama_lengkap'].'" telah pulang sekolah<br>Tanggal : '.tgl_ind($date).'<br>Jam : '.$time.'<br><br>Terimakasih';
          }
  
          if($whatsapp_tipe =='universal'){
            $isipesan  = 'Assalamualaikum wr wb%0ABapak/ibu siswa "'.$data_siswa['nama_lengkap'].'" telah pulang sekolah%0ATanggal : '.tgl_ind($date).'%0AJam : '.$time.'%0A%0ATerimakasih';
            $isipesan = str_replace(' ', '%20', $isipesan);
          }

          $query_absen ="SELECT absen_id,absen_out FROM absen WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
          $result_absen = $connection->query($query_absen);
          if($result_absen->num_rows > 0) {
            $data_absensi = $result_absen->fetch_assoc();

            if($data_absensi['absen_out']=='00:00:00'){
            /*Update Data Absensi */
              $update ="UPDATE absen SET absen_out='$time_absen',
                      status_pulang='$status_pulang' WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
              if($connection->query($update) === false) { 
                echo'Sepertinya Sistem Kami sedang error!'.$audio_fail;
                die($connection->error.__LINE__); 
              } else{
                  echo'success/Selamat Anda berhasil Absen Pulang pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time.', Sampai ketemu besok "'.$data_siswa['nama_lengkap'].'"!'.$audio_success;
                  
                  if($whatsapp_tipe =='wablas'){
                    KirimWa($nomorwa,$isipesan,$link,$token);
                  }
                  if($whatsapp_tipe =='universal'){
                      KirimWa($sender,$nomorwa,$isipesan,$link,$token);
                      
                  }
              }
            }else{
              echo'success/Selamat Anda berhasil Absen Pulang pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time.', Sampai ketemu besok "'.$data_siswa['nama_lengkap'].'"!'.$audio_success;
            }
          }else{
            /** Jika Data absensi masuk tidak ditemukan!*/
            echo'Sebelumnya Siswa '.$data_siswa['nama_lengkap'].' belum pernah Absen masuk!'.$audio_fail;
          }

        }else{
          echo'Hari ini tidak Ada jadwal/jam sekolah!'.$audio_fail;
        }

      }else{
        echo'Qr code/User tidak ditemukan, silahkan hubungi Admin!'.$audio_fail;
      }

    }else{
      foreach ($error as $key => $values) {            
        echo"$values\n";
      }
    }



/** Data Absensi */
break;
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
  echo'Saat ini bbelum ada data absensi terbaru';
}


/* Data COunter */
break;
case'data-counter':
  $query_siswa = "SELECT user_id FROM user";
  $result_siswa = $connection->query($query_siswa);
  $jumlah_siswa = $result_siswa->num_rows;

  $query_absen_masuk ="SELECT absen_id FROM absen WHERE tanggal='$date' AND kehadiran='Hadir'";
  $result_absen_masuk = $connection->query($query_absen_masuk);
  $jumlah_absen_masuk = $result_absen_masuk->num_rows;
  $total_tidak_masuk = $jumlah_siswa - $jumlah_absen_masuk;

  $total_tidak_masuk_pesen = $total_tidak_masuk/$jumlah_siswa * 100;

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