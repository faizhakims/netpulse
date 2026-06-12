<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NetPulse Login</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400&family=Poppins:wght@300;400&display=swap" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html,body{
    width:100%;
    height:100%;
}

body{
    overflow:hidden;
    background:#f1f5f9;
    font-family:'Poppins',sans-serif;
}

/* ================= VANTA BG ================= */
.finisher-header{
    position:fixed;
    inset:0;
    width:100%;
    height:100vh;
    z-index:-3;
}

.overlay{
    position:fixed;
    inset:0;
    background:rgba(241,245,249,.10);
    z-index:-2;
}

/* ================= WRAPPER ================= */
.wrapper{
    position:relative;
    width:100%;
    height:100vh;
}

/* ================= LOGO ================= */
.logo{
    position:absolute;
    left:180px;
    top:50%;
    transform:translateY(-50%);
    width:340px;
    height:auto;
    opacity:.92;
}

/* ================= GLASS CARD ================= */
.login-card{
    position:absolute;

    width:500px;
    min-height:370px;

    right:180px;
    top:50%;
    transform:translateY(-50%);

    border-radius:30px;

    background:
        linear-gradient(
            135deg,
            rgba(255,255,255,.18),
            rgba(255,255,255,.08)
        );

    backdrop-filter: blur(18px) saturate(160%);
    -webkit-backdrop-filter: blur(18px) saturate(160%);

    border:1px solid rgba(255,255,255,.35);

    box-shadow:
        0 8px 32px rgba(0,0,0,.08),
        inset 0 1px 0 rgba(255,255,255,.40);

    padding:34px 42px;

    overflow:hidden;
}

.login-card::before{
    content:'';
    position:absolute;
    inset:0;
    border-radius:30px;
    background:
        linear-gradient(
            120deg,
            rgba(255,255,255,.15),
            transparent 28%,
            transparent 70%,
            rgba(255,255,255,.05)
        );
    pointer-events:none;
}

/* ================= TITLE ================= */
.login-title{
    text-align:center;
    font-family:'Inter',sans-serif;
    font-size:38px;
    font-weight:300;
    color:#111;
    margin-bottom:28px;
}

/* ================= FORM ================= */
.form-group{
    margin-bottom:24px;
}

.form-group label{
    display:block;
    color:rgba(0,0,0,.75);
    font-size:16px;
    margin-bottom:8px;
}

.form-control{
    width:100%;
    height:42px;

    background:transparent;
    border:none;
    border-bottom:1px solid rgba(0,0,0,.20);
    outline:none;

    color:#111;
    font-size:16px;
    padding:0 2px 8px;
}

.form-control::placeholder{
    color:rgba(0,0,0,.30);
}

.form-control:focus{
    border-bottom:1px solid rgba(0,0,0,.65);
}

/* ================= BUTTON ================= */
.login-btn{
    width:100%;
    margin-top:22px;

    border:none;
    background:transparent;
    cursor:pointer;

    display:flex;
    justify-content:center;
    align-items:center;
}

.login-btn:hover .arrow{
    transform:translateX(4px);
    opacity:1;
}

.arrow{
    width:48px;
    height:auto;
    opacity:.70;
    filter: brightness(0);
    transition:.25s ease;
}

/* ================= ERROR ================= */
.error-box{
    background:rgba(220,38,38,.10);
    border:1px solid rgba(220,38,38,.35);
    border-radius:10px;
    padding:10px 14px;
    margin-bottom:20px;
    color:#b91c1c;
    font-size:13px;
    text-align:center;
}

/* ================= MOBILE ================= */
@media(max-width:1100px){

    .logo{
        left:90px;
        width:260px;
    }

    .login-card{
        right:60px;
        transform:translateY(-50%) scale(.95);
    }
}

@media(max-width:900px){

    body{
        overflow:auto;
    }

    .wrapper{
        min-height:100vh;
        display:flex;
        flex-direction:column;
        justify-content:center;
        align-items:center;
        gap:40px;
        padding:30px;
    }

    .logo,
    .login-card{
        position:relative;
        top:auto;
        left:auto;
        right:auto;
        transform:none;
    }

    .logo{
        width:260px;
    }

    .login-card{
        width:95%;
        max-width:500px;
    }
}
</style>
</head>
<body>

<!-- BACKGROUND -->
<div class="finisher-header"></div>
<div class="overlay"></div>

<div class="wrapper">

    <!-- LOGO -->
    <img src="{{ asset('images/netpulseHijau.svg') }}" class="logo">

    <!-- LOGIN CARD -->
    <div class="login-card">

        <div class="login-title">Login</div>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            @if ($errors->any())
            <div class="error-box">
                {{ $errors->first('email') }}
            </div>
            @endif

            <div class="form-group">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="Enter email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Enter password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="login-btn">
                <img src="{{ asset('images/arrowRight.svg') }}" class="arrow">
            </button>

        </form>

    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.net.min.js"></script>

<script>
VANTA.NET({
  el: ".finisher-header",
  mouseControls: true,
  touchControls: true,
  gyroControls: false,
  minHeight: 200.00,
  minWidth: 200.00,
  scale: 1.00,
  scaleMobile: 1.00,
  color: 0x064e3b,
  backgroundColor: 0xf1f5f9
})
</script>

</body>
</html>