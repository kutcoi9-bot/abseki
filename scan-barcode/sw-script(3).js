// FILE: /scan-barcode/sw-script.js

$(document).ready(function() {

    // --- 1. AUDIO SYSTEM (MP3 + BEEP SCAN) ---
    var audioSuccess = new Audio("../sw-content/audio/absen_berhasil.mp3");
    var audioError   = new Audio("../sw-content/audio/absen_gagal.mp3");

    audioSuccess.preload = "auto";
    audioError.preload = "auto";

    var AudioContextClass = window.AudioContext || window.webkitAudioContext;
    var audioCtx = AudioContextClass ? new AudioContextClass() : null;
    var isAudioEnabled = false;

    function setAudioStatus(text, cssClass) {
        $(".audio-status")
            .removeClass("text-muted text-success text-danger text-warning")
            .addClass(cssClass)
            .text(text);
    }

    function unlockAudio() {
        return new Promise(function(resolve, reject) {
            try {
                var jobs = [];

                if (audioCtx && audioCtx.state === "suspended") {
                    jobs.push(audioCtx.resume());
                }

                audioSuccess.muted = true;
                audioError.muted = true;

                jobs.push(
                    audioSuccess.play()
                        .then(function() {
                            audioSuccess.pause();
                            audioSuccess.currentTime = 0;
                            audioSuccess.muted = false;
                        })
                        .catch(function() {
                            audioSuccess.muted = false;
                        })
                );

                jobs.push(
                    audioError.play()
                        .then(function() {
                            audioError.pause();
                            audioError.currentTime = 0;
                            audioError.muted = false;
                        })
                        .catch(function() {
                            audioError.muted = false;
                        })
                );

                Promise.all(jobs).then(function() {
                    isAudioEnabled = true;
                    setAudioStatus("Audio aktif. Scanner siap digunakan.", "text-success");
                    resolve(true);
                }).catch(function(err) {
                    isAudioEnabled = false;
                    setAudioStatus("Audio gagal diaktifkan. Coba klik tombol lagi.", "text-danger");
                    reject(err);
                });
            } catch (e) {
                isAudioEnabled = false;
                setAudioStatus("Browser menolak audio. Coba klik tombol lagi.", "text-danger");
                reject(e);
            }
        });
    }

    function playBeepScan() {
        try {
            if (!audioCtx) return;
            if (audioCtx.state === "suspended") {
                audioCtx.resume().catch(function(){});
            }

            var osc = audioCtx.createOscillator();
            var gain = audioCtx.createGain();

            osc.connect(gain);
            gain.connect(audioCtx.destination);

            var now = audioCtx.currentTime;

            osc.type = "sine";
            osc.frequency.setValueAtTime(1200, now);
            osc.frequency.exponentialRampToValueAtTime(700, now + 0.08);

            gain.gain.setValueAtTime(0.12, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.08);

            osc.start(now);
            osc.stop(now + 0.08);
        } catch (e) {
            console.log("Scan beep error:", e);
        }
    }

    function playSound(type) {
        try {
            if (!isAudioEnabled && type !== "scan") {
                return;
            }

            if (type === "scan") {
                playBeepScan();
            } 
            else if (type === "success") {
                audioSuccess.pause();
                audioSuccess.currentTime = 0;
                audioSuccess.play().catch(function(err) {
                    console.log("Play success audio gagal:", err);
                });
            } 
            else if (type === "error") {
                audioError.pause();
                audioError.currentTime = 0;
                audioError.play().catch(function(err) {
                    console.log("Play error audio gagal:", err);
                });
            }
        } catch (e) {
            console.log("Audio error:", e);
        }
    }

    $("#enableAudioBtn").on("click", function() {
        var $btn = $(this);
        $btn.prop("disabled", true).text("Mengaktifkan Audio...");

        unlockAudio()
            .then(function() {
                playSound("success");
                $btn.text("Audio Aktif");
            })
            .catch(function() {
                $btn.prop("disabled", false).text("Aktifkan Audio");
            });
    });

    // --- 2. CLOCK ---
    setInterval(function() {
        var date = new Date();
        $(".clock").html(date.toLocaleTimeString("id-ID"));
    }, 1000);

    // --- 3. GEOLOCATION ---
    var GEO = { lat: null, lng: null, ok: false };

    function updateGeoStatus(msg, status) {
        $(".geo-status").text(msg);
        $(".geo-status").removeClass("text-warning text-success text-danger");
        if (status == "ok") $(".geo-status").addClass("text-success");
        else if (status == "err") $(".geo-status").addClass("text-danger");
        else $(".geo-status").addClass("text-warning");
    }

    if (navigator.geolocation) {
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

    // --- TOAST NOTIFICATION SYSTEM ---
    function showToast(title, message, type) {
        var bgClass = type === "success" ? "bg-success" : "bg-danger";
        var icon = type === "success" ? "fa-check-circle" : "fa-times-circle";

        var toast = $(
            '<div class="toast-custom ' + bgClass + ' text-white p-3 mb-2 rounded shadow" style="display:none; position:relative; z-index:9999; min-width:250px; border-left:5px solid rgba(0,0,0,0.2);">' +
                '<h6 class="mb-1 font-weight-bold"><i class="fas ' + icon + ' mr-2"></i>' + title + '</h6>' +
                '<span style="font-size:13px;">' + message.replace(/\n/g, "<br>") + "</span>" +
            "</div>"
        );

        if ($("#toast-container").length === 0) {
            $("body").append('<div id="toast-container" style="position:fixed; top:80px; right:20px; z-index:9999; display:flex; flex-direction:column; align-items:flex-end;"></div>');
        }

        $("#toast-container").append(toast);
        toast.slideDown(200);

        setTimeout(function() {
            toast.slideUp(300, function() {
                $(this).remove();
            });
        }, 2500);
    }

    // --- 4. FOCUS HANDLING ---
    $("body").click(function() {
        if (!$(".qrcode").is(":focus")) {
            $(".qrcode").focus();
        }
    });

    // --- 5. HANDLE SUBMIT ---
    var lastCode = "";
    var lastTime = 0;

    $(".form-absen").submit(function(e) {
        e.preventDefault();

        var code = $(".qrcode").val().trim();
        if (code == "") return;

        $(".qrcode").val("").focus();

        if (code.length !== 10) {
            playSound("error");
            showToast("Scan Gagal!", "Barcode tidak utuh (" + code + "). NISN harus 10 angka.", "error");
            return;
        }

        var nowTime = new Date().getTime();
        if (code === lastCode && (nowTime - lastTime) < 2000) {
            return;
        }
        lastCode = code;
        lastTime = nowTime;

        if (!GEO.ok) {
            playSound("error");
            showToast("Lokasi Error!", "Tunggu sampai lokasi terkunci (GPS Aktif).", "error");
            return;
        }

        if (!isAudioEnabled) {
            showToast("Aktifkan Audio", "Klik tombol Aktifkan Audio terlebih dahulu.", "error");
            return;
        }

        $(".submit-loading").show();
        playSound("scan");

        $.ajax({
            url: "./sw-proses.php?action=absen-auto",
            type: "POST",
            data: {
                qrcode: code,
                latitude: GEO.lat,
                longitude: GEO.lng
            },
            success: function(data) {
                setTimeout(function() {
                    $(".submit-loading").hide();
                }, 300);

                var responseText = (data || "").toString().trim();
                var parts = responseText.split("/");
                var status = parts[0] ? parts[0].trim() : "";
                var msg = parts.slice(1).join("/").trim();

                if (!responseText) {
                    status = "error";
                    msg = "Server tidak mengembalikan respons.";
                } else if (parts.length === 1 && status !== "success") {
                    msg = responseText;
                }

                if (status === "success") {
                    playSound("success");
                    showToast("Berhasil!", msg, "success");
                    loadData();
                } else {
                    playSound("error");
                    showToast("Gagal!", msg, "error");
                }
            },
            error: function() {
                $(".submit-loading").hide();
                playSound("error");
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
    setInterval(loadData, 30000);
});