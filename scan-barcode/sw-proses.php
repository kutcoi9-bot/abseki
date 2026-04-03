<?PHP
require_once '../sw-library/sw-config.php';
require_once '../sw-library/sw-function.php';
//require_once '../sw-library/csrf.php';

$ip_login       = $_SERVER['REMOTE_ADDR'];
$created_login  = date('Y-m-d H:i:s');
$iB             = getBrowser();
$browser        = $iB['name'].' '.$iB['version'];
$expired_cookie = time() + 60 * 60 * 24 * 7;

/*
|--------------------------------------------------------------------------
| SETTING KHUSUS NOTIFIKASI WA
|--------------------------------------------------------------------------
*/
$WA_BATCH_CONFIG = array(
  'batch1_min_students'  => 2,
  'masuk_cutoff'         => '09:00:00',
  'pulang_extra_minutes' => 60
);

/*
|--------------------------------------------------------------------------
| DEBUG SETTING
|--------------------------------------------------------------------------
*/
$WA_DEBUG = array(
  'enabled'   => true,
  'file_path' => '../sw-content/debug-wa-batch.log'
);

/*
|--------------------------------------------------------------------------
| DEBUG HELPERS
|--------------------------------------------------------------------------
*/
function debugWaWrite($message){
  global $WA_DEBUG;

  if(!isset($WA_DEBUG['enabled']) || $WA_DEBUG['enabled'] !== true){
    return;
  }

  $file = $WA_DEBUG['file_path'];
  $line = '['.date('Y-m-d H:i:s').'] '.$message.PHP_EOL;
  @file_put_contents($file, $line, FILE_APPEND);
}

function debugWaArray($title, $data = array()){
  $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  debugWaWrite($title.' => '.$encoded);
}

debugWaWrite('=== sw-proses.php aktif dipanggil ===');
debugWaArray('REQUEST', array(
  'action'      => isset($_GET['action']) ? $_GET['action'] : '',
  'method'      => $_SERVER['REQUEST_METHOD'],
  'uri'         => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
  'remote_addr' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
));

/*
|--------------------------------------------------------------------------
| AUTO CREATE LOG TABLE
|--------------------------------------------------------------------------
*/
function ensureWaBatchLogTable($connection){
  $sql = "CREATE TABLE IF NOT EXISTS wa_batch_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tanggal DATE NOT NULL,
            kelas_id INT NOT NULL,
            nama_kelas VARCHAR(100) NOT NULL,
            tipe ENUM('masuk','pulang') NOT NULL,
            batch_ke TINYINT NOT NULL,
            total_siswa INT NOT NULL DEFAULT 0,
            group_name VARCHAR(150) DEFAULT '',
            target_used VARCHAR(255) DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_batch (tanggal, kelas_id, tipe, batch_ke)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  $connection->query($sql);
}
ensureWaBatchLogTable($connection);

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
function esc($connection, $value){
  return $connection->real_escape_string($value);
}

function buildMapsLink($latitude, $longitude){
  if ($latitude != '' && $longitude != '') {
    return 'https://www.google.com/maps?q=' . $latitude . ',' . $longitude;
  }
  return '';
}

function addMinutesToTime($timeString, $minutes){
  $timestamp = strtotime($timeString);
  if ($timestamp === false) {
    return $timeString;
  }
  return date('H:i:s', strtotime('+' . (int)$minutes . ' minutes', $timestamp));
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

/*
|--------------------------------------------------------------------------
| GROUP CONFIG
|--------------------------------------------------------------------------
*/
function getWaGroupMap(){
  return array(

    // ===== KELAS IX =====
    10 => array(
      'nama_kelas'  => 'Kelas IX A',
      'group_name'  => 'Kelas 9A',
      'target'      => '120363418888240227@g.us',
      'active'      => 'Y'
    ),
    11 => array(
      'nama_kelas'  => 'Kelas IX B',
      'group_name'  => 'Kelas 9B',
      'target'      => '120363400906985911@g.us',
      'active'      => 'Y'
    ),
    12 => array(
      'nama_kelas'  => 'Kelas IX C',
      'group_name'  => 'Kelas 9C',
      'target'      => '120363420034489592@g.us',
      'active'      => 'Y'
    ),
    13 => array(
      'nama_kelas'  => 'Kelas IX D',
      'group_name'  => 'Kelas IX D',
      'target'      => '',
      'active'      => 'N'
    ),

    // ===== KELAS VIII =====
    14 => array(
      'nama_kelas'  => 'Kelas VIII A',
      'group_name'  => 'Kelas 8A',
      'target'      => '120363403873331669@g.us',
      'active'      => 'Y'
    ),
    15 => array(
      'nama_kelas'  => 'Kelas VIII B',
      'group_name'  => 'Kelas 8B',
      'target'      => '120363401249844477@g.us',
      'active'      => 'Y'
    ),
    16 => array(
      'nama_kelas'  => 'Kelas VIII C',
      'group_name'  => 'Kelas 8C',
      'target'      => '120363418778503984@g.us',
      'active'      => 'Y'
    ),
    17 => array(
      'nama_kelas'  => 'Kelas VIII D',
      'group_name'  => 'Kelas 8D',
      'target'      => '120363422660628104@g.us',
      'active'      => 'Y'
    ),
    18 => array(
      'nama_kelas'  => 'Kelas VIII E',
      'group_name'  => 'Kelas 8E',
      'target'      => '120363400577116457@g.us',
      'active'      => 'Y'
    ),

    // ===== KELAS VII =====
    19 => array(
      'nama_kelas'  => 'Kelas VII A',
      'group_name'  => 'Kelas 7A',
      'target'      => '120363419167168134@g.us',
      'active'      => 'Y'
    ),
    20 => array(
      'nama_kelas'  => 'Kelas VII B',
      'group_name'  => 'Kelas 7B',
      'target'      => '120363418731658425@g.us',
      'active'      => 'Y'
    ),
    21 => array(
      'nama_kelas'  => 'Kelas VII C',
      'group_name'  => 'Kelas 7C',
      'target'      => '120363417966449855@g.us',
      'active'      => 'Y'
    ),
    23 => array(
      'nama_kelas'  => 'Kelas VII D',
      'group_name'  => 'Kelas 7D',
      'target'      => '120363419549303972@g.us',
      'active'      => 'Y'
    )
  );
}

function getWaGroupConfig($kelas_id, $nama_kelas = ''){
  $map = getWaGroupMap();
  $kelas_id = (int)$kelas_id;

  if(isset($map[$kelas_id])){
    return $map[$kelas_id];
  }

  return array(
    'nama_kelas'  => $nama_kelas,
    'group_name'  => $nama_kelas,
    'target'      => '',
    'active'      => 'N'
  );
}

function getWaTargetFromConfig($groupConfig){
  if(isset($groupConfig['target']) && trim($groupConfig['target']) != ''){
    return trim($groupConfig['target']);
  }
  return '';
}

function dispatchWhatsappMessage($whatsapp_tipe, $groupConfig, $pesan_text, $sender, $link, $token){
  $target = getWaTargetFromConfig($groupConfig);

  debugWaArray('dispatchWhatsappMessage.start', array(
    'whatsapp_tipe' => $whatsapp_tipe,
    'group_name'    => isset($groupConfig['group_name']) ? $groupConfig['group_name'] : '',
    'target'        => $target,
    'payload_len'   => strlen($pesan_text)
  ));

  if($target == ''){
    debugWaWrite('dispatchWhatsappMessage.abort: target grup kosong');
    return array(
      'sent'   => false,
      'target' => '',
      'reason' => 'Target grup WA belum diisi'
    );
  }

  $payload = formatWhatsappPayload($whatsapp_tipe, $pesan_text);

  if($whatsapp_tipe == 'wablas'){
    KirimWa($target, $payload, $link, $token);
    debugWaWrite('dispatchWhatsappMessage.sent via wablas => '.$target);
    return array(
      'sent'   => true,
      'target' => $target,
      'reason' => ''
    );
  }

  if($whatsapp_tipe == 'universal'){
    KirimWa($sender, $target, $payload, $link, $token);
    debugWaWrite('dispatchWhatsappMessage.sent via universal => '.$target);
    return array(
      'sent'   => true,
      'target' => $target,
      'reason' => ''
    );
  }

  debugWaWrite('dispatchWhatsappMessage.abort: tipe WhatsApp tidak dikenali');
  return array(
    'sent'   => false,
    'target' => $target,
    'reason' => 'Tipe WhatsApp tidak dikenali'
  );
}

function hasBatchBeenSent($connection, $tanggal, $kelas_id, $tipe, $batch_ke){
  $tanggal  = esc($connection, $tanggal);
  $kelas_id = (int)$kelas_id;
  $tipe     = esc($connection, $tipe);
  $batch_ke = (int)$batch_ke;

  $sql = "SELECT id
          FROM wa_batch_log
          WHERE tanggal='$tanggal'
            AND kelas_id='$kelas_id'
            AND tipe='$tipe'
            AND batch_ke='$batch_ke'
          LIMIT 1";
  $result = $connection->query($sql);
  return ($result && $result->num_rows > 0);
}

function saveBatchLog($connection, $tanggal, $kelas_id, $nama_kelas, $tipe, $batch_ke, $total_siswa, $group_name, $target_used){
  $tanggal     = esc($connection, $tanggal);
  $kelas_id    = (int)$kelas_id;
  $nama_kelas  = esc($connection, $nama_kelas);
  $tipe        = esc($connection, $tipe);
  $batch_ke    = (int)$batch_ke;
  $total_siswa = (int)$total_siswa;
  $group_name  = esc($connection, $group_name);
  $target_used = esc($connection, $target_used);

  $sql = "INSERT IGNORE INTO wa_batch_log (
            tanggal,
            kelas_id,
            nama_kelas,
            tipe,
            batch_ke,
            total_siswa,
            group_name,
            target_used,
            created_at
          ) VALUES (
            '$tanggal',
            '$kelas_id',
            '$nama_kelas',
            '$tipe',
            '$batch_ke',
            '$total_siswa',
            '$group_name',
            '$target_used',
            NOW()
          )";

  $connection->query($sql);

  debugWaArray('saveBatchLog', array(
    'tanggal'     => $tanggal,
    'kelas_id'    => $kelas_id,
    'nama_kelas'  => $nama_kelas,
    'tipe'        => $tipe,
    'batch_ke'    => $batch_ke,
    'total_siswa' => $total_siswa,
    'group_name'  => $group_name,
    'target_used' => $target_used
  ));
}

function isNotificationWindowClosed($tipe, $current_time, $jadwal, $config){
  if($tipe == 'masuk'){
    return ($current_time >= $config['masuk_cutoff']);
  }

  if($tipe == 'pulang'){
    if(isset($jadwal['jam_pulang']) && $jadwal['jam_pulang'] != ''){
      $batas = addMinutesToTime($jadwal['jam_pulang'], (int)$config['pulang_extra_minutes']);
      return ($current_time >= $batas);
    }
    return false;
  }

  return false;
}

function getAttendanceItemsForBatch($connection, $tanggal, $kelas_id, $tipe){
  $tanggal  = esc($connection, $tanggal);
  $kelas_id = (int)$kelas_id;
  $items    = array();

  if($tipe == 'masuk'){
    $sql = "SELECT
              absen.absen_id,
              user.nama_lengkap,
              absen.absen_in AS jam_scan,
              absen.status_masuk AS status_scan
            FROM absen
            INNER JOIN user ON absen.user_id = user.user_id
            WHERE absen.tanggal = '$tanggal'
              AND user.kelas = '$kelas_id'
              AND absen.absen_in <> '00:00:00'
            ORDER BY absen.absen_in ASC, absen.absen_id ASC";
  } else {
    $sql = "SELECT
              absen.absen_id,
              user.nama_lengkap,
              absen.absen_out AS jam_scan,
              absen.status_pulang AS status_scan
            FROM absen
            INNER JOIN user ON absen.user_id = user.user_id
            WHERE absen.tanggal = '$tanggal'
              AND user.kelas = '$kelas_id'
              AND absen.absen_out <> '00:00:00'
            ORDER BY absen.absen_out ASC, absen.absen_id ASC";
  }

  $result = $connection->query($sql);
  if($result){
    while($row = $result->fetch_assoc()){
      $items[] = $row;
    }
  }

  return $items;
}

function buildBatchMessage($tipe, $tanggal, $batch_ke, $items){
  $label = ($tipe == 'masuk') ? 'MASUK' : 'PULANG';
  $labelPengiriman = ($batch_ke == 1) ? 'pertama' : 'kedua';

  $pesan  = "Assalamualaikum Wr. Wb.\n\n";
  $pesan .= "Berikut laporan absensi ".$label." siswa hari ini.\n\n";
  $pesan .= "Tanggal: ".tgl_ind($tanggal)."\n";
  $pesan .= "Pengiriman: ".$labelPengiriman."\n\n";
  $pesan .= "Daftar siswa:\n";

  $no = 1;
  foreach($items as $item){
    $nama   = trim($item['nama_lengkap']);
    $jam    = trim($item['jam_scan']);
    $status = trim($item['status_scan']);

    if($status == ''){
      $status = '-';
    }

    $pesan .= $no.". ".$nama." - ".$jam." - ".$status."\n";
    $no++;
  }

  $pesan .= "\nTerima kasih.\n\n";
  $pesan .= "Wassalamualaikum Wr. Wb.";

  return $pesan;
}

function processClassNotificationBatches($connection, $kelas_id, $nama_kelas, $tipe, $tanggal, $current_time, $jadwal, $whatsapp_tipe, $sender, $link, $token, $config){
  $kelas_id    = (int)$kelas_id;
  $nama_kelas  = trim($nama_kelas);
  $groupConfig = getWaGroupConfig($kelas_id, $nama_kelas);

  if(!isset($groupConfig['active']) || $groupConfig['active'] != 'Y'){
    debugWaArray('processClassNotificationBatches.skip.inactive', array(
      'kelas_id'   => $kelas_id,
      'nama_kelas' => $nama_kelas,
      'tipe'       => $tipe
    ));
    return;
  }

  $items = getAttendanceItemsForBatch($connection, $tanggal, $kelas_id, $tipe);
  $totalItems = count($items);

  if($totalItems <= 0){
    debugWaArray('processClassNotificationBatches.skip.no_items', array(
      'kelas_id'   => $kelas_id,
      'nama_kelas' => $nama_kelas,
      'tipe'       => $tipe
    ));
    return;
  }

  $threshold = (int)$config['batch1_min_students'];
  if($threshold < 1){
    $threshold = 1;
  }

  $batch1       = array_slice($items, 0, $threshold);
  $batch2       = array_slice($items, $threshold);
  $windowClosed = isNotificationWindowClosed($tipe, $current_time, $jadwal, $config);

  debugWaArray('processClassNotificationBatches.state', array(
    'kelas_id'       => $kelas_id,
    'nama_kelas'     => $nama_kelas,
    'tipe'           => $tipe,
    'tanggal'        => $tanggal,
    'current_time'   => $current_time,
    'threshold'      => $threshold,
    'total_items'    => $totalItems,
    'batch1_count'   => count($batch1),
    'batch2_count'   => count($batch2),
    'window_closed'  => $windowClosed,
    'target_group'   => isset($groupConfig['target']) ? $groupConfig['target'] : ''
  ));

  if(
    !hasBatchBeenSent($connection, $tanggal, $kelas_id, $tipe, 1) &&
    count($batch1) > 0 &&
    ($totalItems >= $threshold || $windowClosed)
  ){
    $pesan = buildBatchMessage($tipe, $tanggal, 1, $batch1);
    $send  = dispatchWhatsappMessage($whatsapp_tipe, $groupConfig, $pesan, $sender, $link, $token);

    debugWaArray('batch1.result', array(
      'kelas_id'   => $kelas_id,
      'nama_kelas' => $nama_kelas,
      'tipe'       => $tipe,
      'sent'       => $send['sent'],
      'target'     => $send['target'],
      'reason'     => $send['reason']
    ));

    if($send['sent'] === true){
      saveBatchLog(
        $connection,
        $tanggal,
        $kelas_id,
        $nama_kelas,
        $tipe,
        1,
        count($batch1),
        $groupConfig['group_name'],
        $send['target']
      );
    }
  }

  if(
    !hasBatchBeenSent($connection, $tanggal, $kelas_id, $tipe, 2) &&
    count($batch2) > 0 &&
    $windowClosed
  ){
    $pesan = buildBatchMessage($tipe, $tanggal, 2, $batch2);
    $send  = dispatchWhatsappMessage($whatsapp_tipe, $groupConfig, $pesan, $sender, $link, $token);

    debugWaArray('batch2.result', array(
      'kelas_id'   => $kelas_id,
      'nama_kelas' => $nama_kelas,
      'tipe'       => $tipe,
      'sent'       => $send['sent'],
      'target'     => $send['target'],
      'reason'     => $send['reason']
    ));

    if($send['sent'] === true){
      saveBatchLog(
        $connection,
        $tanggal,
        $kelas_id,
        $nama_kelas,
        $tipe,
        2,
        count($batch2),
        $groupConfig['group_name'],
        $send['target']
      );
    }
  }
}

/*
|--------------------------------------------------------------------------
| MAIN
|--------------------------------------------------------------------------
*/
switch (@$_GET['action']){

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

debugWaArray('absen-auto.input', array(
  'qrcode'    => isset($qrcode) ? $qrcode : '',
  'latitude'  => $latitude,
  'longitude' => $longitude
));

if (empty($error)){

  $query_siswa = "SELECT
                    user.user_id,
                    user.nama_lengkap,
                    user.telp,
                    user.avatar,
                    user.kelas AS kelas_id,
                    kelas.nama_kelas
                  FROM user
                  INNER JOIN kelas ON user.kelas = kelas.kelas_id
                  WHERE user.nisn='$qrcode'";
  $result_siswa = $connection->query($query_siswa);

  if ($result_siswa->num_rows > 0){
    $data_siswa = $result_siswa->fetch_assoc();

    debugWaArray('absen-auto.siswa', $data_siswa);

    $query_waktu = "SELECT jam_masuk,jam_telat,jam_pulang FROM waktu WHERE hari='$hari_ini' AND active='Y'";
    $result_waktu = $connection->query($query_waktu);

    if($result_waktu->num_rows > 0){
      $data_waktu = $result_waktu->fetch_assoc();

      debugWaArray('absen-auto.jadwal', $data_waktu);

      if($time < '10:00:00'){
        if($data_waktu['jam_telat'] > $time){
          $status_masuk = 'Tepat Waktu';
        }else{
          $status_masuk = 'Telat';
        }

        $query_absen = "SELECT absen_id FROM absen WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
        $result_absen = $connection->query($query_absen);

        if(!$result_absen->num_rows > 0){

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
            debugWaWrite('INSERT absen masuk gagal: '.$connection->error);
            echo 'Sepertinya Sistem Kami sedang error!';
            die($connection->error.__LINE__);
          } else{
            debugWaWrite('INSERT absen masuk berhasil untuk user_id='.$data_siswa['user_id']);

            processClassNotificationBatches(
              $connection,
              $data_siswa['kelas_id'],
              $data_siswa['nama_kelas'],
              'masuk',
              $date,
              $time,
              $data_waktu,
              $whatsapp_tipe,
              $sender,
              $link,
              $token,
              $WA_BATCH_CONFIG
            );

            echo 'success/[BATCH-GRUP-AKTIF] Terimakasih "'.$data_siswa['nama_lengkap'].'", Absensi masuk berhasil pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.' Selamat Belajar!';
          }
        }else{
          debugWaWrite('Absensi masuk sudah ada untuk user_id='.$data_siswa['user_id']);

          processClassNotificationBatches(
            $connection,
            $data_siswa['kelas_id'],
            $data_siswa['nama_kelas'],
            'masuk',
            $date,
            $time,
            $data_waktu,
            $whatsapp_tipe,
            $sender,
            $link,
            $token,
            $WA_BATCH_CONFIG
          );

          echo 'success/[BATCH-GRUP-AKTIF] Terimakasih "'.$data_siswa['nama_lengkap'].'", Absensi masuk sudah tercatat hari ini.';
        }

      }else{
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
              debugWaWrite('UPDATE absen pulang gagal: '.$connection->error);
              echo 'Sepertinya Sistem Kami sedang error!';
              die($connection->error.__LINE__);
            } else{
              debugWaWrite('UPDATE absen pulang berhasil untuk user_id='.$data_siswa['user_id']);

              processClassNotificationBatches(
                $connection,
                $data_siswa['kelas_id'],
                $data_siswa['nama_kelas'],
                'masuk',
                $date,
                $time,
                $data_waktu,
                $whatsapp_tipe,
                $sender,
                $link,
                $token,
                $WA_BATCH_CONFIG
              );

              processClassNotificationBatches(
                $connection,
                $data_siswa['kelas_id'],
                $data_siswa['nama_kelas'],
                'pulang',
                $date,
                $time,
                $data_waktu,
                $whatsapp_tipe,
                $sender,
                $link,
                $token,
                $WA_BATCH_CONFIG
              );

              echo 'success/[BATCH-GRUP-AKTIF] Selamat Anda berhasil Absen Pulang pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time.', Sampai ketemu besok "'.$data_siswa['nama_lengkap'].'"!';
            }

          }else{
            debugWaWrite('Absensi pulang sudah ada untuk user_id='.$data_siswa['user_id']);

            processClassNotificationBatches(
              $connection,
              $data_siswa['kelas_id'],
              $data_siswa['nama_kelas'],
              'masuk',
              $date,
              $time,
              $data_waktu,
              $whatsapp_tipe,
              $sender,
              $link,
              $token,
              $WA_BATCH_CONFIG
            );

            processClassNotificationBatches(
              $connection,
              $data_siswa['kelas_id'],
              $data_siswa['nama_kelas'],
              'pulang',
              $date,
              $time,
              $data_waktu,
              $whatsapp_tipe,
              $sender,
              $link,
              $token,
              $WA_BATCH_CONFIG
            );

            echo 'success/[BATCH-GRUP-AKTIF] Absensi pulang "'.$data_siswa['nama_lengkap'].'" sudah tercatat hari ini.';
          }
        }else{
          debugWaWrite('Absensi pulang ditolak karena belum ada absen masuk untuk user_id='.$data_siswa['user_id']);
          echo 'Sebelumnya Siswa '.$data_siswa['nama_lengkap'].' belum pernah Absen masuk!';
        }
      }

    }else{
      debugWaWrite('Tidak ada jadwal aktif hari ini');
      echo 'Hari ini tidak Ada jadwal/jam sekolah!';
    }

  }else{
    debugWaWrite('QR/User tidak ditemukan untuk qrcode='.$qrcode);
    echo 'Qr code/User tidak ditemukan, silahkan hubungi Admin!';
  }

}else{
  foreach ($error as $key => $values) {
    debugWaWrite('Validation error: '.$values);
    echo "$values\n";
  }
}
break;

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

  $query_siswa = "SELECT
                    user.user_id,
                    user.nama_lengkap,
                    user.telp,
                    user.avatar,
                    user.kelas AS kelas_id,
                    kelas.nama_kelas
                  FROM user
                  INNER JOIN kelas ON user.kelas = kelas.kelas_id
                  WHERE user.nisn='$qrcode'";
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
          debugWaWrite('INSERT manual absen masuk gagal: '.$connection->error);
          echo 'Sepertinya Sistem Kami sedang error!';
          die($connection->error.__LINE__);
        } else{
          processClassNotificationBatches(
            $connection,
            $data_siswa['kelas_id'],
            $data_siswa['nama_kelas'],
            'masuk',
            $date,
            $time,
            $data_waktu,
            $whatsapp_tipe,
            $sender,
            $link,
            $token,
            $WA_BATCH_CONFIG
          );

          echo 'success/[BATCH-GRUP-AKTIF] Terimakasih "'.$data_siswa['nama_lengkap'].'", Absensi Anda berhasil pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.' Selamat Belajar!';
        }
      }else{
        processClassNotificationBatches(
          $connection,
          $data_siswa['kelas_id'],
          $data_siswa['nama_kelas'],
          'masuk',
          $date,
          $time,
          $data_waktu,
          $whatsapp_tipe,
          $sender,
          $link,
          $token,
          $WA_BATCH_CONFIG
        );

        echo 'success/[BATCH-GRUP-AKTIF] Terimakasih "'.$data_siswa['nama_lengkap'].'", Absensi Anda berhasil pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.' Selamat Belajar!';
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
  $query_siswa = "SELECT
                    user.user_id,
                    user.nama_lengkap,
                    user.telp,
                    user.avatar,
                    user.kelas AS kelas_id,
                    kelas.nama_kelas
                  FROM user
                  INNER JOIN kelas ON user.kelas = kelas.kelas_id
                  WHERE user.nisn='$qrcode'";
  $result_siswa = $connection->query($query_siswa);

  if ($result_siswa->num_rows > 0){
    $data_siswa = $result_siswa->fetch_assoc();

    $query_waktu = "SELECT jam_masuk,jam_telat,jam_pulang FROM waktu WHERE hari='$hari_ini' AND active='Y'";
    $result_waktu = $connection->query($query_waktu);

    if($result_waktu->num_rows > 0){
      $data_waktu = $result_waktu->fetch_assoc();

      if($time < $data_waktu['jam_pulang']){
        $status_pulang ='Pulang Cepat';
      }else{
        $status_pulang ='Tepat Waktu';
      }

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
            debugWaWrite('UPDATE manual absen pulang gagal: '.$connection->error);
            echo 'Sepertinya Sistem Kami sedang error!';
            die($connection->error.__LINE__);
          } else{
            processClassNotificationBatches(
              $connection,
              $data_siswa['kelas_id'],
              $data_siswa['nama_kelas'],
              'masuk',
              $date,
              $time,
              $data_waktu,
              $whatsapp_tipe,
              $sender,
              $link,
              $token,
              $WA_BATCH_CONFIG
            );

            processClassNotificationBatches(
              $connection,
              $data_siswa['kelas_id'],
              $data_siswa['nama_kelas'],
              'pulang',
              $date,
              $time,
              $data_waktu,
              $whatsapp_tipe,
              $sender,
              $link,
              $token,
              $WA_BATCH_CONFIG
            );

            echo 'success/[BATCH-GRUP-AKTIF] Selamat Anda berhasil Absen Pulang pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time.', Sampai ketemu besok "'.$data_siswa['nama_lengkap'].'"!';
          }
        }else{
          processClassNotificationBatches(
            $connection,
            $data_siswa['kelas_id'],
            $data_siswa['nama_kelas'],
            'masuk',
            $date,
            $time,
            $data_waktu,
            $whatsapp_tipe,
            $sender,
            $link,
            $token,
            $WA_BATCH_CONFIG
          );

          processClassNotificationBatches(
            $connection,
            $data_siswa['kelas_id'],
            $data_siswa['nama_kelas'],
            'pulang',
            $date,
            $time,
            $data_waktu,
            $whatsapp_tipe,
            $sender,
            $link,
            $token,
            $WA_BATCH_CONFIG
          );

          echo 'success/[BATCH-GRUP-AKTIF] Selamat Anda berhasil Absen Pulang pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time.', Sampai ketemu besok "'.$data_siswa['nama_lengkap'].'"!';
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

case 'data-absensi':
$query_absen ="SELECT
                absen.tanggal,
                absen.absen_in,
                absen.absen_out,
                absen.status_masuk,
                absen.status_pulang,
                user.nama_lengkap,
                user.kelas,
                user.avatar
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