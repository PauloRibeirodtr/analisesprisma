<style>
    :root {
        --prisma-navy: #0b1557;
        --prisma-blue: #294cff;
        --prisma-violet: #6218ef;
        --prisma-cyan: #19cee7;
        --prisma-text: #18213d;
        --prisma-muted: #71809f;
    }

    body.login-page,
    body.register-page {
        position: relative;
        min-height: 100vh;
        height: auto;
        overflow-x: hidden;
        padding: 36px 20px;
        background:
            radial-gradient(circle at 12% 12%, rgba(32, 211, 232, .18), transparent 28%),
            radial-gradient(circle at 88% 84%, rgba(97, 24, 239, .14), transparent 28%),
            linear-gradient(135deg, #f9fbff 0%, #f3f5ff 50%, #fbfaff 100%);
        font-family: "Source Sans Pro", sans-serif;
    }

    body.login-page::before,
    body.login-page::after,
    body.register-page::before,
    body.register-page::after {
        position: fixed;
        z-index: 0;
        border: 1px solid rgba(42, 76, 255, .1);
        border-radius: 50%;
        content: "";
        pointer-events: none;
    }

    body.login-page::before,
    body.register-page::before {
        top: -160px;
        right: -110px;
        width: 410px;
        height: 410px;
        box-shadow: 0 0 0 56px rgba(33, 203, 232, .035), 0 0 0 112px rgba(42, 76, 255, .025);
    }

    body.login-page::after,
    body.register-page::after {
        bottom: -190px;
        left: -150px;
        width: 380px;
        height: 380px;
        box-shadow: 0 0 0 48px rgba(98, 24, 239, .03), 0 0 0 96px rgba(33, 203, 232, .025);
    }

    .login-page .login-box,
    .register-page .register-box {
        z-index: 1;
        width: 100%;
        max-width: 440px;
        margin: auto;
    }

    .register-page .register-box {
        max-width: 470px;
    }

    .login-page .login-logo,
    .register-page .register-logo {
        display: none;
    }

    .login-page .card,
    .register-page .card {
        overflow: hidden;
        margin: 0;
        border: 1px solid rgba(39, 66, 167, .09);
        border-top: 4px solid transparent;
        border-radius: 24px;
        background:
            linear-gradient(#fff, #fff) padding-box,
            linear-gradient(90deg, var(--prisma-blue), var(--prisma-cyan), var(--prisma-violet)) border-box;
        box-shadow: 0 24px 70px rgba(24, 38, 100, .14);
    }

    .login-page .card-header,
    .register-page .card-header {
        padding: 24px 34px 18px;
        border-bottom: 0;
        background: #fff;
    }

    .login-page .card-header .card-title,
    .register-page .card-header .card-title {
        width: 100%;
        margin: 0;
    }

    .prisma-auth-brand {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .prisma-auth-brand__symbol {
        width: 142px;
        height: 114px;
        margin-bottom: 2px;
        filter: drop-shadow(0 12px 13px rgba(29, 75, 219, .16));
    }

    .register-page .prisma-auth-brand__symbol {
        width: 118px;
        height: 92px;
    }

    .prisma-auth-brand__name {
        display: flex;
        align-items: baseline;
        gap: 8px;
        color: var(--prisma-navy);
        line-height: 1;
    }

    .prisma-auth-brand__name span {
        font-size: 17px;
        font-weight: 400;
        letter-spacing: .16em;
    }

    .prisma-auth-brand__name strong {
        background: linear-gradient(90deg, var(--prisma-navy), var(--prisma-blue) 62%, var(--prisma-cyan));
        background-clip: text;
        color: transparent;
        font-size: 30px;
        font-weight: 800;
        letter-spacing: .08em;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .prisma-auth-brand__tagline {
        display: block;
        margin: 8px 0 0;
        color: #53658d;
        font-size: 14px;
        font-weight: 400;
        letter-spacing: .08em;
    }

    .login-page .login-card-body,
    .register-page .register-card-body {
        padding: 8px 38px 30px;
        color: var(--prisma-text);
    }

    .prisma-auth-intro {
        margin-bottom: 24px;
        text-align: center;
    }

    .prisma-auth-intro h1 {
        margin: 0 0 6px;
        color: var(--prisma-text);
        font-size: 24px;
        font-weight: 700;
    }

    .prisma-auth-intro p {
        max-width: 340px;
        margin: 0 auto;
        color: var(--prisma-muted);
        font-size: 15px;
        line-height: 1.45;
    }

    .prisma-auth-intro__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        margin-bottom: 13px;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(42, 76, 255, .11), rgba(27, 207, 230, .16));
        color: var(--prisma-blue);
        font-size: 20px;
    }

    .login-page .form-group,
    .register-page .form-group {
        margin-bottom: 18px;
    }

    .login-page .form-group label,
    .register-page .form-group label {
        margin-bottom: 7px;
        color: #34415f;
        font-size: 14px;
        font-weight: 600;
    }

    .prisma-auth-input {
        border: 1px solid #dce2ef;
        border-radius: 12px;
        background: #f9fbff;
        transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
    }

    .prisma-auth-input:focus-within {
        border-color: var(--prisma-blue);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(42, 76, 255, .1);
    }

    .prisma-auth-input .form-control,
    .prisma-auth-input .input-group-text {
        height: 48px;
        border: 0;
        background: transparent;
    }

    .prisma-auth-input .form-control {
        padding-left: 15px;
        color: var(--prisma-text);
    }

    .prisma-auth-input .form-control:focus {
        box-shadow: none;
    }

    .prisma-auth-input .form-control::placeholder {
        color: #a3aec3;
    }

    .prisma-auth-input .input-group-text {
        padding-right: 15px;
        color: #7281a5;
    }

    .prisma-auth-input:focus-within .input-group-text {
        color: var(--prisma-blue);
    }

    .prisma-auth-input .form-control.is-invalid {
        background-image: none;
    }

    .prisma-auth-input:has(.is-invalid) {
        border-color: #dc3545;
    }

    .prisma-auth-input .invalid-feedback {
        width: 100%;
        margin: 7px 4px 0;
    }

    .prisma-auth-recovery {
        margin-bottom: 7px;
        color: var(--prisma-blue);
        font-size: 13px;
        font-weight: 600;
    }

    .prisma-auth-recovery:hover {
        color: var(--prisma-violet);
    }

    .prisma-auth-options {
        margin: 4px 0 22px;
    }

    .prisma-auth-options .icheck-primary label {
        color: #596783;
        font-size: 14px;
        font-weight: 400;
    }

    .prisma-auth-button {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 49px;
        border: 0;
        border-radius: 12px;
        background: linear-gradient(100deg, var(--prisma-navy), var(--prisma-blue) 54%, var(--prisma-violet));
        box-shadow: 0 12px 24px rgba(42, 76, 255, .24);
        color: #fff;
        font-size: 16px;
        font-weight: 700;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .prisma-auth-button:hover,
    .prisma-auth-button:focus {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 16px 28px rgba(42, 76, 255, .3);
    }

    .prisma-auth-button .fas {
        font-size: 13px;
    }

    .prisma-auth-alert {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 20px;
        padding: 13px 14px;
        border: 1px solid rgba(14, 159, 110, .16);
        border-radius: 12px;
        background: rgba(20, 184, 122, .08);
        color: #137a57;
        font-size: 14px;
        line-height: 1.4;
    }

    .prisma-auth-alert .fas {
        margin-top: 3px;
    }

    .login-page .card-footer,
    .register-page .card-footer {
        padding: 18px 30px;
        border-top: 1px solid #edf0f6;
        background: #f9fbff;
        text-align: center;
    }

    .login-page .card-footer p,
    .register-page .card-footer p {
        margin: 0;
        color: var(--prisma-muted);
        font-size: 14px;
    }

    .login-page .card-footer a,
    .register-page .card-footer a,
    .prisma-auth-link-button {
        color: var(--prisma-blue);
        font-weight: 700;
    }

    .login-page .card-footer a,
    .register-page .card-footer a {
        margin-left: 3px;
    }

    .prisma-auth-link-button:hover,
    .prisma-auth-link-button:focus {
        color: var(--prisma-violet);
        text-decoration: none;
    }

    @media (max-width: 575.98px) {
        body.login-page,
        body.register-page {
            align-items: flex-start;
            justify-content: flex-start;
            padding: 18px 12px;
        }

        .login-page .card,
        .register-page .card {
            border-radius: 19px;
        }

        .login-page .card-header,
        .register-page .card-header {
            padding: 19px 22px 14px;
        }

        .login-page .login-card-body,
        .register-page .register-card-body {
            padding: 8px 24px 25px;
        }

        .prisma-auth-brand__symbol,
        .register-page .prisma-auth-brand__symbol {
            width: 122px;
            height: 96px;
        }

        .prisma-auth-brand__name span {
            font-size: 14px;
        }

        .prisma-auth-brand__name strong {
            font-size: 25px;
        }

        .prisma-auth-brand__tagline {
            font-size: 12px;
        }

        .prisma-auth-intro h1 {
            font-size: 22px;
        }
    }
</style>
