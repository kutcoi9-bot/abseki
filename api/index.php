<?php
require_once'../sw-library/sw-config.php';
include_once'../sw-library/sw-function.php';
header("Content-Type:application/json");

if(!empty($_GET['uid'])){
	$uid 	= mysqli_real_escape_string($connection,$_GET['uid']);
	
	$query_siswa ="SELECT user_id,nama_lengkap,telp FROM user WHERE rfid='$uid'";
	$result_siswa = $connection->query($query_siswa);
	if ($result_siswa->num_rows > 0) {
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
  
		  if($data_waktu['jam_telat'] > $time){
			$status_pulang ='Pulang Cepat';
		  }else{
			$status_pulang ='Tepat Waktu';
		  }

		  $query_absen ="SELECT absen_id FROM absen WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
			$result_absen = $connection->query($query_absen);
			if(!$result_absen->num_rows > 0) {
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
					echo'Sepertinya Sistem Kami sedang error!';
					die($connection->error.__LINE__); 
				} else{
					$data['status'] = 'VALID';
					$data['nama'] = strip_tags(htmlspecialchars($data_siswa['nama_lengkap']));
					$data['waktu'] = $time_absen;
					$data['tanggal'] = tanggal_ind($date);
				}

			}else{
				$data['status'] = 'VALID';
				$data['nama'] = strip_tags(htmlspecialchars($data_siswa['nama_lengkap']));
				$data['waktu'] = $time_absen;
				$data['tanggal'] = tanggal_ind($date);
			}

		}else{
			$data['status'] = 'INVALID_JAM';
		}

	}else{
		$data['status'] = 'INVALID_UID';
	}

	echo json_encode($data);
}?>