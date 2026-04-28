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
    background:#000;
    font-family:'Poppins',sans-serif;
}

/* ================= FINISHER BG ================= */
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
    background:rgba(0,0,0,.05);
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

/* ================= CLEAR GLASS CARD ================= */
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
            rgba(255,255,255,.04),
            rgba(255,255,255,.01)
        );

    backdrop-filter: blur(14px) saturate(160%);
    -webkit-backdrop-filter: blur(14px) saturate(160%);

    border:1px solid rgba(255,255,255,.08);

    box-shadow:
        0 8px 20px rgba(0,0,0,.08),
        inset 0 1px 0 rgba(255,255,255,.08);

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
            rgba(255,255,255,.05),
            transparent 28%,
            transparent 70%,
            rgba(255,255,255,.01)
        );
    pointer-events:none;
}

/* ================= TITLE ================= */
.login-title{
    text-align:center;
    font-family:'Inter',sans-serif;
    font-size:38px;
    font-weight:300;
    color:#fff;
    margin-bottom:28px;
}

/* ================= FORM ================= */
.form-group{
    margin-bottom:24px;
}

.form-group label{
    display:block;
    color:rgba(255,255,255,.92);
    font-size:16px;
    margin-bottom:8px;
}

.form-control{
    width:100%;
    height:42px;

    background:transparent;
    border:none;
    border-bottom:1px solid rgba(255,255,255,.20);
    outline:none;

    color:#fff;
    font-size:16px;
    padding:0 2px 8px;
}

.form-control::placeholder{
    color:rgba(255,255,255,.35);
}

.form-control:focus{
    border-bottom:1px solid rgba(255,255,255,.75);
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
    opacity:.85;
    transition:.25s ease;
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
    <img src="{{ asset('images/netpulse.svg') }}" class="logo">

    <!-- LOGIN CARD -->
    <div class="login-card">

        <div class="login-title">Login</div>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            {{-- Error message --}}
            @if ($errors->any())
            <div style="
                background:rgba(220,38,38,.15);
                border:1px solid rgba(220,38,38,.4);
                border-radius:10px;
                padding:10px 14px;
                margin-bottom:20px;
                color:#fca5a5;
                font-size:13px;
                text-align:center;
            ">
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

<script src="{{ asset('js/finisher-header.es5.min.js') }}" type="text/javascript"></script>

<script type="text/javascript">
new FinisherHeader({
  "count": 5,
  "size": {
    "min": 900,
    "max": 1500,
    "pulse": 0
  },
  "speed": {
    "x": {
      "min": 0,
      "max": 0.3
    },
    "y": {
      "min": 0,
      "max": 0
    }
  },
  "colors": {
    "background": "#000000",
    "particles": [
      "#949292",
      "#222222",
      "#000000"
    ]
  },
  "blending": "lighten",
  "opacity": {
    "center": 0.15,
    "edge": 0.05
  },
  "skew": 0,
  "shapes": [
    "s"
  ]
});
</script>

</body>
</html>