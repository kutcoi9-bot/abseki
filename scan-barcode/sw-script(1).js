// FILE: /scan-barcode/sw-script.js

$(document).ready(function() {
    
    // --- 1. AUDIO SYSTEM (Tanpa MP3 File) ---
    var audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    
    function playSound(type) {
        if(audioCtx.state === 'suspended') audioCtx.resume();
        
        var osc = audioCtx.createOscillator();
        var gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        
        var now = audioCtx.currentTime;
        
        if(type == 'scan') {
            // Suara "Bip!" pendek (Saat scanner menembak)
            osc.type = 'sine';
            osc.frequency.setValueAtTime(1200, now);
            osc.frequency.exponentialRampToValueAtTime(500, now + 0.1);
            gain.gain.setValueAtTime(0.1, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.1);
            osc.start(now);
            osc.stop(now + 0.1);
        }
        else if(type == 'success') {
            // Suara "Ting-Ting" (Nada Sukses)
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(600, now);
            osc.frequency.setValueAtTime(1200, now + 0.1);
            gain.gain.setValueAtTime(0.1, now);
            gain.gain.linearRampToValueAtTime(0, now + 0.5);
            osc.start(now);
            osc.stop(now + 0.5);
        } 
        else if(type == 'error') {
            // Suara "Tetoot.." (Nada Gagal)
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(150, now);
            osc.frequency.linearRampToValueAtTime(100, now + 0.3);
            gain.gain.setValueAtTime(0.1, now);
            gain.gain.linearRampToValueAtTime(0, now + 0.4);
            osc.start(now);
            osc.stop(now + 0.4);
        }
    }

    // --- 2. CLOCK ---
    setInterval(function() {
        var date = new Date();
        $(".clock").html(date.toLocaleTimeString('id-ID'));
    }, 1000);

    // --- 3. GEOLOCATION ---
    var GEO = { lat: null, lng: null, ok: false };
    
    function updateGeoStatus(msg, status) {
        $(".geo-status").text(msg);
        $(".geo-status").removeClass("text-warning text-success text-danger");
        if(status == 'ok') $(".geo-status").addClass("text-success");
        else if(status == 'err') $(".geo-status").addClass("text-danger");
        else $(".geo-status").addClass("text-warning");
    }

    if(navigator.geolocation) {
        navigator.geolocation.watchPosition(function(pos) {
            GEO.lat = pos.coords.latitude;
            GEO.lng = pos.coords.longitude;
            GEO.ok = true;
            updateGeoStatus("Lokasi Terkunci", "ok");
        }, function(err) {
            GEO.ok = false;
            updateGeoStatus("Gagal deteksi lokasi: " + err.message, "err");
        }, { enableHighAccuracy: true });
    } else {
        updateGeoStatus("Browser tidak support GPS", "err");
    }

    // --- TOAST NOTIFICATION SYSTEM (PENGGANTI SWAL AGAR TIDAK BLOCKING) ---
    // Fungsi ini membuat notifikasi melayang yang tidak menghilangkan fokus dari input text
    function showToast(title, message, type) {
        var bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        
        var toast = $(
            '<div class="toast-custom ' + bgClass + ' text-white p-3 mb-2 rounded shadow" style="display:none; position:relative; z-index:9999; min-width: 250px; border-left: 5px solid rgba(0,0,0,0.2);">' +
                '<h6 class="mb-1 font-weight-bold"><i class="fas ' + icon + ' mr-2"></i>' + title + '</h6>' +
                '<span style="font-size: 13px;">' + message.replace(/\n/g, '<br>') + '</span>' +
            '</div>'
        );
        
        // Buat container jika belum ada (jarak dari atas disesuaikan dengan header)
        if ($("#toast-container").length === 0) {
            $("body").append('<div id="toast-container" style="position:fixed; top:80px; right:20px; z-index:9999; display:flex; flex-direction:column; align-items:flex-end;"></div>');
        }
        
        $("#toast-container").append(toast);
        toast.slideDown(200);
        
        // Hilangkan otomatis dalam 2.5 detik
        setTimeout(function() {
            toast.slideUp(300, function() { $(this).remove(); });
        }, 2500);
    }

    // --- 4. FOCUS HANDLING ---
    // Memastikan kursor kembali jika user klik area kosong
    $("body").click(function(){ 
        if(!$(".qrcode").is(":focus")) {
            $(".qrcode").focus(); 
        }
    });

    // --- 5. HANDLE SUBMIT ---
    var lastCode = "";
    var lastTime = 0;

    $(".form-absen").submit(function(e) {
        e.preventDefault();
        
        var code = $(".qrcode").val().trim();
        if(code == "") return;

        // --- SOLUSI KECEPATAN: LANGSUNG RESET INPUT ---
        // Kosongkan dan fokuskan input detik ini juga.
        // Dengan ini, siswa berikutnya bisa langsung menembak barcode 
        // meskipun proses AJAX sebelumnya masih berjalan di latar belakang.
        $(".qrcode").val("").focus();

        // --- SOLUSI PARTIAL READ (DISESUAIKAN FORMAT NISN 10 DIGIT) ---
        // Karena NISN pada database selalu 10 angka, kita tolak otomatis jika tidak pas 10 digit.
        // Ini 100% mencegah error karena scanner hanya membaca sebagian.
        if(code.length !== 10) {
            playSound('error');
            showToast("Scan Gagal!", "Barcode tidak utuh (" + code + "). NISN harus 10 angka.", "error");
            return;
        }

        // --- SOLUSI ANTI DOUBLE-SCAN ---
        // Mencegah alat scanner mengirim input ganda (Enter 2x beruntun) untuk siswa yang SAMA
        var nowTime = new Date().getTime();
        if(code === lastCode && (nowTime - lastTime) < 2000) {
            // Jika kode sama persis dan di-scan di bawah 2 detik, abaikan diam-diam
            return; 
        }
        lastCode = code;
        lastTime = nowTime;

        // Validasi Lokasi (Pastikan GPS sudah aktif)
        if(!GEO.ok) {
            playSound('error');
            showToast("Lokasi Error!", "Tunggu sampai lokasi terkunci (GPS Aktif).", "error");
            return;
        }

        $(".submit-loading").show();
        playSound('scan'); // Bunyi Bip Scan 

        // Proses ke Server
        $.ajax({
            url: "./sw-proses.php?action=absen-auto",
            type: "POST",
            data: {
                qrcode: code,
                latitude: GEO.lat,
                longitude: GEO.lng
            },
            success: function(data) {
                // Beri sedikit jeda mematikan loading agar visual terlihat natural
                setTimeout(function() { $(".submit-loading").hide(); }, 300);

                var parts = data.split("/");
                var status = parts[0];
                var msg = parts.slice(1).join("/");

                if(status == 'success') {
                    playSound('success');
                    showToast("Berhasil!", msg, "success");
                    loadData(); // Refresh list riwayat absen di sebelah kanan
                } else {
                    playSound('error');
                    showToast("Gagal!", msg, "error");
                }
            },
            error: function() {
                $(".submit-loading").hide();
                playSound('error');
                showToast("Koneksi Error", "Koneksi terputus ke server.", "error");
            }
        });
    });

    // --- 6. LOAD DATA RIWAYAT ---
    function loadData() {
        $(".data-absensi").load("./sw-proses.php?action=data-absensi");
        $(".data-counter").load("./sw-proses.php?action=data-counter");
    }
    loadData();
    setInterval(loadData, 30000); // Auto refresh data secara umum tiap 30 detik
});