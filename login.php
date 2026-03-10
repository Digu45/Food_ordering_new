<?php
session_start();
require_once 'config.php';
require_once 'connection.php';

if (!empty($_SESSION['mobile_verified'])) {
  header('Location: home.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
  $entered = trim($_POST['otp'] ?? '');
  $mobile  = preg_replace('/\D/', '', $_POST['mob_no'] ?? '');
  $name    = htmlspecialchars(trim($_POST['name'] ?? 'Guest'));

  if (empty($_SESSION['otp'])) {
    $error = 'Session expired. Please send OTP again.';
  } elseif (time() - ($_SESSION['otp_time'] ?? 0) > 300) {
    $error = 'OTP expired. Please resend.';
  } elseif ($entered != $_SESSION['otp']) {
    $error = 'Wrong OTP. Please try again.';
  } else {
    $_SESSION['mobile']             = $mobile;
    $_SESSION['name']               = $name;
    $_SESSION['mobile_verified']    = true;
    $_SESSION['mobile_for_history'] = $mobile;
    if (!isset($_SESSION['unique_device_id']))
      $_SESSION['unique_device_id'] = md5(uniqid(rand(), true));
    unset($_SESSION['otp'], $_SESSION['otp_time'], $_SESSION['otp_mobile']);
    header('Location: home.php');
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
  <title>Login | <?= RESTAURANT_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html,
    body {
      height: 100%;
      font-family: 'DM Sans', sans-serif;
    }

    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      min-height: -webkit-fill-available;
      background: #fff;
    }

    input,
    button {
      font-family: inherit;
    }

    input {
      outline: none;
      -webkit-appearance: none;
    }

    .header-band {
      background: linear-gradient(135deg, #e65c00, #c0392b);
      padding: 52px 24px 44px;
      text-align: center;
      flex-shrink: 0;
    }

    .form-card {
      flex: 1;
      background: #fff;
      border-radius: 28px 28px 0 0;
      margin-top: -20px;
      padding: 30px 22px 40px;
      overflow-y: auto;
    }

    .inp {
      display: block;
      width: 100%;
      padding: 15px 16px;
      border: 2px solid #e5e7eb;
      border-radius: 14px;
      font-size: 16px;
      background: #fafafa;
      transition: border-color .2s;
    }

    .inp:focus {
      border-color: #e65c00;
      background: #fff;
    }

    .btn-main {
      display: block;
      width: 100%;
      padding: 16px;
      border: none;
      border-radius: 14px;
      background: linear-gradient(135deg, #e65c00, #f9a84d);
      color: #fff;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
    }

    .btn-main:disabled {
      opacity: .55;
      cursor: not-allowed;
    }

    .btn-ghost {
      display: block;
      width: 100%;
      padding: 13px;
      border: 2px solid #e5e7eb;
      border-radius: 14px;
      background: none;
      font-size: 14px;
      font-weight: 600;
      color: #6b7280;
      cursor: pointer;
    }

    .otp-inp {
      letter-spacing: 12px;
      text-align: center;
      font-size: 28px;
      font-weight: 700;
    }

    .lbl {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #374151;
      margin-bottom: 8px;
    }
  </style>
</head>

<body>

  <div class="header-band">
    <div style="width:68px;height:68px;background:rgba(255,255,255,.2);border-radius:22px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
      <i class="fas fa-utensils" style="color:#fff;font-size:28px;"></i>
    </div>
    <h1 style="font-family:'Playfair Display',serif;font-size:26px;font-weight:900;color:#fff;margin-bottom:6px;"><?= RESTAURANT_NAME ?></h1>
    <p style="color:rgba(255,255,255,.75);font-size:14px;">Verify mobile to place your order</p>
  </div>

  <div class="form-card">

    <?php if ($error): ?>
      <div style="background:#fef2f2;border:1.5px solid #fca5a5;color:#dc2626;padding:12px 16px;border-radius:12px;font-size:13px;margin-bottom:20px;">
        <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i><?= $error ?>
      </div>
    <?php endif; ?>

    <!-- STEP 1 -->
    <div id="s1">
      <p style="color:#6b7280;font-size:14px;margin-bottom:22px;">Enter your details to receive OTP</p>
      <div style="margin-bottom:18px;">
        <label class="lbl">Your Name</label>
        <input type="text" id="f_name" class="inp" placeholder="e.g. Rahul Patil" autocomplete="name" />
      </div>
      <div style="margin-bottom:26px;">
        <label class="lbl">Mobile Number</label>
        <div style="display:flex;gap:10px;">
          <div style="padding:0 14px;background:#f3f4f6;border:2px solid #e5e7eb;border-radius:14px;display:flex;align-items:center;font-weight:700;color:#374151;font-size:14px;flex-shrink:0;">+91</div>
          <input type="tel" id="f_mobile" class="inp" placeholder="10-digit number" maxlength="10" inputmode="numeric" />
        </div>
      </div>
      <button class="btn-main" id="sendBtn" type="button">
        <i class="fas fa-paper-plane" style="margin-right:8px;"></i>Send OTP
      </button>
    </div>

    <!-- STEP 2 -->
    <form id="s2" style="display:none;" method="POST" action="login.php">
      <input type="hidden" name="mob_no" id="h_mobile" />
      <input type="hidden" name="name" id="h_name" />
      <input type="hidden" name="verify_otp" value="1" />

      <div style="text-align:center;margin-bottom:20px;">
        <div style="width:60px;height:60px;background:#dcfce7;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
          <i class="fas fa-check" style="color:#16a34a;font-size:24px;"></i>
        </div>
        <p style="font-weight:600;color:#111;font-size:15px;margin-bottom:4px;">OTP sent to</p>
        <p style="color:#e65c00;font-weight:700;font-size:18px;" id="lbl_num"></p>
      </div>

      <!-- Shown only if SMS failed -->
      <div id="otpFallbackBox" style="display:none;background:#fff7ed;border:2px dashed #f97316;border-radius:14px;padding:16px;text-align:center;margin-bottom:20px;">
        <p style="font-size:12px;color:#9a3412;font-weight:600;margin-bottom:6px;">
          <i class="fas fa-exclamation-triangle" style="margin-right:5px;"></i>SMS could not be sent. Use this OTP:
        </p>
        <p id="otpDisplay" style="font-size:38px;font-weight:900;letter-spacing:10px;color:#c2410c;font-family:monospace;"></p>
      </div>

      <div style="margin-bottom:22px;">
        <label class="lbl" style="text-align:center;">Enter 6-digit OTP</label>
        <input type="tel" name="otp" id="f_otp" class="inp otp-inp"
          placeholder="——————" maxlength="6" inputmode="numeric" autocomplete="one-time-code" />
      </div>

      <button type="submit" class="btn-main" style="margin-bottom:12px;">
        <i class="fas fa-shield-alt" style="margin-right:8px;"></i>Verify &amp; Login
      </button>

      <button type="button" id="resendBtn" class="btn-ghost" style="margin-bottom:10px;" disabled>
        Resend in <span id="t">60</span>s
      </button>

      <button type="button" class="btn-ghost" onclick="goBack()" style="border-color:transparent;color:#9ca3af;">
        ← Change Number
      </button>
    </form>

  </div>

  <script>
    $(function() {

      $('#sendBtn').on('click', function() {
        var name = $('#f_name').val().trim();
        var mobile = $('#f_mobile').val().trim();
        if (!name) {
          alert('Please enter your name.');
          return;
        }
        if (!/^\d{10}$/.test(mobile)) {
          alert('Enter a valid 10-digit mobile number.');
          return;
        }

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i>Sending…';

        $.post('send_otp.php', {
          mobile: mobile
        }, function(res) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right:8px;"></i>Send OTP';

          $('#h_mobile').val(mobile);
          $('#h_name').val(name);
          $('#lbl_num').text('+91 ' + mobile);
          $('#f_otp').val('');

          // If SMS failed, show OTP on screen as fallback
          if (!res.sms && res.otp) {
            $('#otpDisplay').text(res.otp);
            $('#otpFallbackBox').show();
          } else {
            $('#otpFallbackBox').hide();
          }

          $('#s1').hide();
          $('#s2').show();
          $('#f_otp').focus();
          startTimer(60);

        }, 'json').fail(function() {
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right:8px;"></i>Send OTP';
          alert('Something went wrong. Please try again.');
        });
      });

      $('#resendBtn').on('click', function() {
        if (this.disabled) return;
        $.post('send_otp.php', {
          mobile: $('#h_mobile').val()
        }, function(res) {
          if (!res.sms && res.otp) {
            $('#otpDisplay').text(res.otp);
            $('#otpFallbackBox').show();
          } else {
            $('#otpFallbackBox').hide();
          }
          $('#f_otp').val('');
          startTimer(60);
        }, 'json');
      });

    });

    function startTimer(sec) {
      var el = document.getElementById('t');
      var btn = document.getElementById('resendBtn');
      btn.disabled = true;
      btn.style.color = '#9ca3af';
      btn.style.borderColor = '#e5e7eb';
      var iv = setInterval(function() {
        el.textContent = --sec;
        if (sec <= 0) {
          clearInterval(iv);
          btn.disabled = false;
          btn.textContent = 'Resend OTP';
          btn.style.color = '#e65c00';
          btn.style.borderColor = '#e65c00';
        }
      }, 1000);
    }

    function goBack() {
      $('#s2').hide();
      $('#s1').show();
      $('#f_otp').val('');
    }
  </script>
</body>

</html>