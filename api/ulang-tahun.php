<?php
require_once'../sw-library/sw-config.php';
include_once'../sw-library/sw-function.php';
$jam = date('G'); 
$date = date('Y-m-d');

//$token = 'yV5l7FPBZ5NsXhNZyatqpSajfVvsdV';
//$sender = '628813993416';
//$link = 'https://wabot.smkadisumarmo.sch.id/send-message';
    
    if ($jam >= 6 && $jam < 17) {
        $query_siswa = "SELECT user_id, nama_lengkap, telp FROM user WHERE DATE_FORMAT(tanggal_lahir, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')";
        $result_siswa = $connection->query($query_siswa);

        if ($result_siswa->num_rows > 0) {
            while ($data_siswa = $result_siswa->fetch_assoc()) {
                $userId = $data_siswa['user_id'];
                $nama_lengkap = strip_tags($data_siswa['nama_lengkap']);
                $nomorwa = strip_tags($data_siswa['telp']);

                $isipesan  = 'Selamat ulang tahun (' . $nama_lengkap . '), %0ASemoga selalu diberikan kebahagiaan dan kesuksesan!';
                $isipesan = str_replace(' ', '%20', $isipesan);

                // Cek apakah sudah pernah dikirim hari ini
                $check_query = "SELECT notifikasi_id FROM notifikasi WHERE user_id = '$userId' AND tipe='birthday' AND status='Y' AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
                $check = $connection->query($check_query);

                if ($check->num_rows == 0) {

                    // Kirim notifikasi via WA
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "".$link."?api_key=".$token."&sender=".$sender."&number=".$nomorwa."&message=".$isipesan."");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response_wa = curl_exec($ch);
                    curl_close($ch);
                    //echo "$response_wa\n";

                    echo'Berhasil dikirim!';
                    // Simpan data ke tabel notifikasi
                    $insert_query = "INSERT INTO notifikasi (user_id, date, tipe, status) VALUES ('$userId', '$date', 'birthday', 'Y')";
                    $connection->query($insert_query);
                    
                }else{
                    echo'Sudah pernah dikirim!';
                }
            }
        } else {
            echo 'Tidak ada data ulan tahun hari ini!';
        }
    }else{
        echo'Di luar jam kirim notifikasi';
    }

// */10 * * * * /usr/bin/php /home/username/public_html/api/ulang-tahun.php
$connection->close();?>

