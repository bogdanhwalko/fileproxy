<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FileProxy')</title>
    <!-- <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png"> -->
     <link rel="icon" href="{{ asset('favicon2.ico') }}" type="image/x-icon">
    <style>
        :root {
            color-scheme: light;
            --bg: #f3f5fb;
            --surface: #ffffff;
            --surface-muted: #eef1f9;
            --surface-subtle: #f7f9fd;
            --line: #dfe3ed;
            --line-strong: #c5ccda;
            --text: #0b1020;
            --muted: #5b6478;
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --primary-soft: #ebeafc;
            --accent: #06b6d4;
            --accent-soft: #e0f7fb;
            --danger: #e11d48;
            --danger-soft: #ffe4ea;
            --success: #059669;
            --success-soft: #d6f5e7;
            --ink: #0b1020;
            --shadow: 0 18px 46px rgb(15 23 42 / 12%);
            --shadow-soft: 0 10px 28px rgb(15 23 42 / 7%);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                linear-gradient(180deg, #fafbff 0, var(--bg) 340px),
                var(--bg);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, Helvetica, sans-serif;
            letter-spacing: 0;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select {
            font: inherit;
        }

        .page {
            width: min(1280px, calc(100% - 36px));
            margin: 0 auto;
            padding: 22px 0 44px;
        }

        .site-nav,
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .site-nav {
            padding-bottom: 30px;
        }

        .topbar {
            margin-bottom: 20px;
            padding: 12px;
            border: 1px solid rgb(223 227 237 / 85%);
            border-radius: 8px;
            background: rgb(255 255 255 / 82%);
            box-shadow: var(--shadow-soft);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .brand-mark {
            display: grid;
            width: 44px;
            height: 44px;
            place-items: center;
            border-radius: 8px;
            background: var(--ink);
            color: #fff;
            font-weight: 700;
            box-shadow: inset 0 0 0 1px rgb(255 255 255 / 14%);
        }

        .brand h1,
        .brand strong {
            display: block;
            margin: 0;
            color: var(--text);
            font-size: 24px;
            line-height: 1.1;
        }

        .brand p {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .nav-actions,
        .actions,
        .upload-actions,
        .auth-row,
        .pagination-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-actions {
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .user-chip {
            color: var(--muted);
            font-size: 14px;
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(320px, 480px);
            gap: 36px;
            align-items: center;
            min-height: 560px;
            padding: 36px 0 48px;
        }

        .hero-copy {
            max-width: 640px;
        }

        .eyebrow {
            margin: 0 0 14px;
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hero h1 {
            margin: 0;
            color: var(--ink);
            font-size: 52px;
            line-height: 1.04;
        }

        .hero p {
            margin: 18px 0 0;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.6;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 26px;
        }

        .welcome-hero {
            display: grid;
            gap: 28px;
            min-height: 500px;
            align-content: center;
            padding: 44px 0 34px;
            border-bottom: 1px solid var(--line);
        }

        .welcome-copy {
            width: min(780px, 100%);
        }

        .welcome-copy h1 {
            margin: 0;
            color: var(--ink);
            font-size: 58px;
            line-height: 1.02;
        }

        .welcome-copy p:not(.eyebrow) {
            margin: 18px 0 0;
            width: min(720px, 100%);
            color: var(--muted);
            font-size: 18px;
            line-height: 1.6;
        }

        .welcome-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            width: min(900px, 100%);
        }

        .welcome-summary div {
            min-width: 0;
            padding: 13px 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .welcome-summary span,
        .welcome-summary strong {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .welcome-summary span {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .welcome-summary strong {
            margin-top: 7px;
            color: var(--text);
            font-size: 15px;
        }

        .welcome-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            padding: 22px 0 0;
        }

        .welcome-card {
            min-width: 0;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .welcome-card-index {
            display: inline-flex;
            margin-bottom: 18px;
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 700;
        }

        .welcome-card strong {
            display: block;
            font-size: 17px;
        }

        .welcome-card p {
            margin: 9px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .feature-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 24px;
        }

        .feature {
            padding: 14px 0;
            border-top: 1px solid var(--line);
        }

        .feature strong {
            display: block;
            font-size: 15px;
        }

        .feature span {
            display: block;
            margin-top: 6px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
        }

        .landing-nav {
            padding-bottom: 18px;
        }

        .mobile-cta-bar {
            display: none;
        }

        .landing-panel {
            display: grid;
            gap: 22px;
            padding: 28px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background:
                linear-gradient(135deg, rgb(255 255 255 / 96%) 0%, rgb(247 249 253 / 90%) 54%, rgb(233 238 252 / 72%) 100%),
                #fff;
            box-shadow: var(--shadow);
        }

        .landing-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.02fr) minmax(390px, 0.98fr);
            gap: 28px;
            align-items: center;
        }

        .landing-hero-copy {
            width: min(880px, 100%);
        }

        .landing-label {
            display: inline-flex;
            min-height: 30px;
            align-items: center;
            padding: 6px 10px;
            border: 1px solid #dfe3ed;
            border-radius: 999px;
            background: var(--success-soft);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .landing-hero-copy h1 {
            margin: 18px 0 0;
            width: min(940px, 100%);
            color: var(--ink);
            font-size: 60px;
            line-height: 1.02;
        }

        .landing-hero-copy p {
            margin: 18px 0 0;
            width: min(760px, 100%);
            color: var(--muted);
            font-size: 18px;
            line-height: 1.65;
        }

        .landing-actions {
            margin-top: 24px;
        }

        .landing-points {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        .landing-points span {
            display: inline-flex;
            min-height: 32px;
            align-items: center;
            padding: 7px 10px;
            border: 1px solid rgb(223 227 237 / 90%);
            border-radius: 999px;
            background: rgb(255 255 255 / 72%);
            color: var(--text);
            font-size: 13px;
            font-weight: 800;
            box-shadow: var(--shadow-soft);
        }

        .landing-metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .landing-metrics div,
        .landing-benefits article,
        .landing-flow div {
            min-width: 0;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .landing-metrics strong,
        .landing-metrics span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .landing-metrics strong {
            color: var(--ink);
            font-size: 24px;
            line-height: 1;
        }

        .landing-metrics span {
            margin-top: 8px;
            color: var(--muted);
            font-size: 13px;
        }

        .landing-snapshot {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: 0 22px 58px rgb(11 16 32 / 13%);
        }

        .snapshot-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 14px;
            border-bottom: 1px solid var(--line);
            background: #111827;
            color: #fff;
        }

        .snapshot-toolbar div {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 0;
        }

        .snapshot-toolbar strong,
        .snapshot-toolbar span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .snapshot-toolbar span {
            color: rgb(255 255 255 / 68%);
            font-size: 13px;
        }

        .snapshot-dot {
            width: 10px;
            height: 10px;
            flex: 0 0 auto;
            border-radius: 999px;
            background: var(--success);
        }

        .snapshot-body {
            display: grid;
            grid-template-columns: minmax(180px, 240px) minmax(0, 1fr);
            min-height: 280px;
        }

        .snapshot-folders {
            display: grid;
            align-content: start;
            gap: 8px;
            padding: 14px;
            border-right: 1px solid var(--line);
            background: #fafbfd;
        }

        .snapshot-folders strong {
            margin-bottom: 4px;
            color: var(--ink);
            font-size: 14px;
        }

        .snapshot-folders span {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            min-height: 38px;
            align-items: center;
            padding: 8px 10px;
            border-radius: 8px;
            color: var(--text);
            font-size: 13px;
        }

        .snapshot-folders span:first-of-type {
            background: var(--surface-muted);
            box-shadow: inset 3px 0 0 var(--primary);
        }

        .snapshot-folders b {
            color: var(--muted);
            font-size: 12px;
        }

        .snapshot-main {
            display: grid;
            gap: 12px;
            align-content: start;
            padding: 14px;
        }

        .snapshot-upload {
            display: grid;
            grid-template-columns: 46px minmax(0, 1fr);
            gap: 12px;
            align-items: center;
            padding: 14px;
            border: 1px dashed #aab4c5;
            border-radius: 8px;
            background: #eaeefc;
        }

        .snapshot-upload > span {
            display: grid;
            width: 46px;
            height: 46px;
            place-items: center;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            font-size: 22px;
            font-weight: 800;
        }

        .snapshot-upload strong,
        .snapshot-upload small {
            display: block;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .snapshot-upload strong {
            color: var(--ink);
            font-size: 15px;
        }

        .snapshot-upload small {
            margin-top: 4px;
            color: var(--muted);
            font-size: 13px;
        }

        .snapshot-files {
            display: grid;
            gap: 8px;
        }

        .snapshot-files div {
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            min-height: 50px;
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
        }

        .snapshot-files .file-icon {
            width: 40px;
            min-height: 30px;
            padding: 5px 6px;
            border-radius: 6px;
            font-size: 11px;
        }

        .snapshot-files strong {
            min-width: 0;
            overflow: hidden;
            color: var(--text);
            font-size: 13px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .snapshot-files small {
            justify-self: end;
            padding: 5px 8px;
            border-radius: 999px;
            background: var(--surface-muted);
            color: var(--muted);
            font-size: 12px;
            white-space: nowrap;
        }

        .landing-section {
            display: grid;
            gap: 16px;
            padding-top: 28px;
        }

        .landing-section-head {
            width: min(780px, 100%);
        }

        .landing-section-head h2,
        .landing-final h2 {
            margin: 10px 0 0;
            color: var(--ink);
            font-size: 34px;
            line-height: 1.14;
        }

        .landing-benefits {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .landing-benefits article {
            display: grid;
            align-content: start;
            gap: 8px;
        }

        .landing-benefits article > span {
            display: inline-grid;
            width: 34px;
            height: 28px;
            place-items: center;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: 12px;
            font-weight: 900;
        }

        .landing-benefits strong,
        .landing-flow strong,
        .landing-audience strong {
            display: block;
            color: var(--ink);
            font-size: 17px;
        }

        .landing-benefits p,
        .landing-flow p,
        .landing-audience p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .landing-flow {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            padding-top: 18px;
        }

        .landing-flow span {
            display: inline-grid;
            width: 32px;
            height: 32px;
            margin-bottom: 18px;
            place-items: center;
            border-radius: 8px;
            background: var(--ink);
            color: #fff;
            font-size: 13px;
            font-weight: 800;
        }

        .landing-use-cases {
            padding-top: 26px;
        }

        .landing-audience {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .landing-audience article {
            display: grid;
            gap: 9px;
            min-width: 0;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background:
                linear-gradient(180deg, #fff 0%, var(--surface-subtle) 100%),
                var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .landing-final {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-top: 28px;
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--ink);
            box-shadow: var(--shadow);
        }

        .landing-final .landing-label {
            border-color: rgb(255 255 255 / 14%);
            background: rgb(255 255 255 / 10%);
            color: #cbd5e7;
        }

        .landing-final h2 {
            color: #fff;
            font-size: 28px;
        }

        .landing-final .button {
            flex: 0 0 auto;
            background: #fff;
            color: var(--ink);
            box-shadow: none;
        }

        .landing-final .button:hover {
            background: #eef1f9;
            color: var(--ink);
        }

        .file-type {
            border-radius: 6px;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: 12px;
            font-weight: 700;
            text-align: center;
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .auth-panel {
            width: min(440px, 100%);
            margin: 32px auto 0;
            padding: 24px;
        }

        .auth-panel h1 {
            margin: 0;
            font-size: 26px;
        }

        .auth-panel p {
            margin: 8px 0 0;
            color: var(--muted);
            line-height: 1.5;
        }

        .auth-form {
            display: grid;
            gap: 14px;
            margin-top: 20px;
        }

        .telegram-card {
            display: grid;
            gap: 10px;
            margin-top: 18px;
            padding: 14px;
            border: 1px solid #a8e7f1;
            border-radius: 8px;
            background: var(--accent-soft);
        }

        .telegram-card strong {
            color: var(--ink);
            font-size: 15px;
        }

        .telegram-card p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
        }

        .command-pill {
            display: block;
            overflow-x: auto;
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface-subtle);
            color: var(--text);
            font-family: Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 13px;
            white-space: nowrap;
        }

        .local-code {
            display: inline-grid;
            min-height: 46px;
            place-items: center;
            padding: 8px 16px;
            border: 1px solid #a7e0c8;
            border-radius: 8px;
            background: var(--success-soft);
            color: var(--success);
            font-family: Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0;
        }

        .field-group {
            display: grid;
            gap: 7px;
        }

        .field-group label {
            font-size: 14px;
            font-weight: 700;
        }

        .field {
            width: 100%;
            min-height: 44px;
            border: 1px solid var(--line-strong);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
            padding: 10px 12px;
            transition: border-color 160ms ease, box-shadow 160ms ease, background 160ms ease;
        }

        .field:focus {
            border-color: var(--primary);
            outline: 0;
            box-shadow: 0 0 0 4px rgb(79 70 229 / 10%);
        }

        .required-mark {
            color: var(--danger);
        }

        .phone-input {
            display: grid;
            grid-template-columns: 66px minmax(0, 1fr);
            min-height: 46px;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .phone-input:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgb(79 70 229 / 10%);
        }

        .phone-prefix {
            display: grid;
            place-items: center;
            border-right: 1px solid var(--line);
            background: var(--surface-subtle);
            color: var(--ink);
            font-weight: 700;
            white-space: nowrap;
        }

        .phone-input .field {
            min-height: 44px;
            border: 0;
            border-radius: 0;
            background: transparent;
        }

        .phone-input .field:focus {
            outline: 0;
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 14px;
        }

        .status {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 0 0 18px;
            padding: 14px 16px;
            border: 1px solid #a7e0c8;
            border-radius: 8px;
            background: var(--success-soft);
            color: var(--success);
            font-size: 14px;
            line-height: 1.45;
            box-shadow: var(--shadow-soft);
        }

        .status::before {
            content: "OK";
            flex: 0 0 auto;
            padding: 2px 6px;
            border-radius: 999px;
            background: #bce9d3;
            color: var(--success);
            font-size: 11px;
            font-weight: 700;
        }

        .errors {
            display: grid;
            gap: 8px;
            margin: 0 0 18px;
            padding: 14px 16px;
            border: 1px solid #e9b9c0;
            border-radius: 8px;
            background: var(--danger-soft);
            color: #7c2635;
            font-size: 14px;
            line-height: 1.45;
            box-shadow: var(--shadow-soft);
        }

        .errors strong {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .errors strong::before {
            content: "!";
            display: inline-grid;
            width: 20px;
            height: 20px;
            place-items: center;
            border-radius: 999px;
            background: #f5ccd3;
            color: #7c2635;
            font-size: 12px;
            font-weight: 700;
        }

        .errors ul {
            margin: 0;
            padding-left: 28px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 10px 16px;
            border: 1px solid transparent;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 22px rgb(79 70 229 / 16%);
            transition: background 160ms ease, border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
        }

        .button:hover {
            background: var(--primary-dark);
            box-shadow: 0 12px 28px rgb(55 48 163 / 22%);
            transform: translateY(-1px);
        }

        .button.secondary {
            background: rgb(255 255 255 / 78%);
            border-color: var(--line);
            color: var(--text);
            font-weight: 600;
            box-shadow: none;
        }

        .button.secondary:hover {
            border-color: var(--line-strong);
            background: var(--surface-subtle);
            box-shadow: 0 8px 18px rgb(15 23 42 / 7%);
        }

        .button.accent {
            background: #fff;
            border-color: #a8e7f1;
            color: var(--accent);
            box-shadow: none;
        }

        .button.accent:hover {
            background: var(--accent-soft);
            box-shadow: 0 8px 18px rgb(6 182 212 / 10%);
        }

        .button.danger {
            background: #fff;
            border-color: #edc2ca;
            color: var(--danger);
            box-shadow: none;
        }

        .button.danger:hover {
            background: var(--danger-soft);
            box-shadow: 0 8px 18px rgb(225 29 72 / 8%);
        }

        .button.link {
            min-height: auto;
            padding: 0;
            border: 0;
            background: transparent;
            color: var(--primary-dark);
        }

        .button.link:hover {
            background: transparent;
            color: var(--text);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }

        .stat {
            position: relative;
            min-height: 88px;
            overflow: hidden;
            padding: 15px 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .stat::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 3px;
            background: var(--primary);
        }

        .stat span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .stat strong {
            display: block;
            margin-top: 10px;
            color: var(--ink);
            font-size: 28px;
            line-height: 1;
        }

        .workspace {
            display: grid;
            grid-template-columns: minmax(250px, 300px) minmax(0, 1fr);
            gap: 16px;
            align-items: start;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            align-items: start;
        }

        .settings-compact-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(340px, 0.92fr);
            gap: 14px;
            align-items: start;
        }

        .settings-panel-groups {
            order: 1;
        }

        .settings-panel-bots {
            order: 2;
        }

        .settings-guide {
            margin-bottom: 14px;
        }

        .panel-header.compact {
            padding: 14px 16px 0;
        }

        .guide-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 8px;
            padding: 14px 16px;
        }

        .guide-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px 0;
        }

        .guide-actions span {
            min-width: 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .guide-steps div {
            min-width: 0;
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fafbfd;
        }

        .guide-steps strong,
        .guide-steps span {
            display: block;
            overflow: hidden;
        }

        .guide-steps strong {
            font-size: 13px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .guide-steps span {
            margin-top: 5px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .settings-note {
            margin: 0 16px 16px;
            padding: 10px 12px;
            border: 1px solid #a8e7f1;
            border-radius: 8px;
            background: var(--accent-soft);
            color: #0e7490;
            font-size: 13px;
            line-height: 1.45;
        }

        .system-limit-note {
            border-color: #bce0d0;
            background: var(--success-soft);
            color: var(--primary-dark);
        }

        .settings-form {
            display: grid;
            gap: 14px;
            padding: 18px;
        }

        .compact-settings-form {
            grid-template-columns: minmax(130px, 1fr) minmax(130px, 1fr) minmax(180px, 1.2fr) auto auto;
            align-items: end;
            gap: 10px;
            padding: 14px 16px;
        }

        .settings-compact-grid .compact-settings-form {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            padding: 10px 12px;
        }

        .settings-compact-grid .compact-settings-form .field-group:nth-of-type(3) {
            grid-column: 1 / -1;
        }

        .compact-settings-form .field {
            min-height: 38px;
            padding: 8px 10px;
        }

        .settings-compact-grid .compact-settings-form .field {
            min-height: 34px;
            padding: 7px 9px;
        }

        .compact-settings-form .button {
            min-height: 38px;
            padding: 8px 12px;
        }

        .settings-compact-grid .compact-settings-form .button {
            min-height: 34px;
            padding: 7px 10px;
        }

        .compact-checkbox {
            min-height: 38px;
            align-items: center;
            padding-bottom: 2px;
            white-space: nowrap;
        }

        .settings-compact-grid .compact-checkbox {
            min-height: 34px;
        }

        .settings-list {
            display: grid;
            gap: 10px;
            padding: 18px;
            border-top: 1px solid var(--line);
        }

        .settings-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
        }

        .settings-item strong,
        .settings-item span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .settings-item span {
            margin-top: 4px;
            color: var(--muted);
            font-size: 13px;
        }

        .token-mask {
            color: var(--muted);
            font-family: Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 12px;
        }

        .settings-table-wrap {
            overflow-x: auto;
            border-top: 1px solid var(--line);
        }

        .settings-table {
            min-width: 760px;
        }

        .settings-compact-grid .settings-table {
            width: 100%;
            min-width: 0;
            table-layout: fixed;
        }

        .settings-table th,
        .settings-table td {
            padding: 9px 12px;
            font-size: 13px;
        }

        .settings-compact-grid .settings-table th,
        .settings-compact-grid .settings-table td {
            padding: 7px 8px;
            font-size: 12px;
            vertical-align: top;
        }

        .settings-table th {
            font-size: 11px;
        }

        .settings-compact-grid .settings-table th {
            font-size: 10px;
        }

        .settings-table td strong,
        .settings-table td > span {
            display: block;
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .settings-compact-grid .settings-table td strong,
        .settings-compact-grid .settings-table td > span {
            max-width: 150px;
        }

        .settings-compact-grid .settings-table th:last-child,
        .settings-compact-grid .settings-table td:last-child {
            width: 132px;
        }

        .settings-table td > span {
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
        }

        .settings-table .badge-row {
            display: flex;
            margin-top: 5px;
        }

        .table-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 6px;
        }

        .settings-compact-grid .table-actions {
            justify-content: flex-start;
            gap: 4px;
        }

        .table-actions .button {
            min-height: 30px;
            padding: 5px 8px;
            font-size: 12px;
        }

        .settings-compact-grid .table-actions .button {
            min-height: 27px;
            padding: 4px 7px;
            font-size: 11px;
        }

        .compact-empty {
            padding: 18px;
        }

        .badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            min-height: 22px;
            padding: 3px 8px;
            border-radius: 999px;
            background: var(--surface-muted);
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .badge.success {
            background: var(--success-soft);
            color: var(--success);
        }

        .badge.danger {
            background: var(--danger-soft);
            color: var(--danger);
        }

        .badge.accent {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .sidebar-stack {
            display: grid;
            gap: 18px;
        }

        .panel-header {
            padding: 18px 18px 0;
        }

        .panel-header h2 {
            margin: 0;
            font-size: 18px;
        }

        .panel-header p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
        }

        .upload-panel {
            margin-bottom: 16px;
            overflow: hidden;
        }

        .upload-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            padding: 18px;
            border-bottom: 1px solid var(--line);
            background: var(--surface-subtle);
        }

        .upload-panel-head h2 {
            margin: 4px 0 0;
            color: var(--ink);
            font-size: 22px;
        }

        .upload-panel-head p {
            margin: 7px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
        }

        .section-kicker {
            display: inline-flex;
            color: var(--primary);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .upload-limit {
            flex: 0 0 auto;
            min-width: 130px;
            padding: 11px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            text-align: right;
        }

        .upload-limit span,
        .upload-limit strong {
            display: block;
        }

        .upload-limit span {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .upload-limit strong {
            margin-top: 4px;
            color: var(--ink);
            font-size: 20px;
        }

        .upload-form {
            display: grid;
            gap: 12px;
            padding: 18px;
        }

        .premium-upload-form {
            grid-template-columns: minmax(290px, 0.72fr) minmax(0, 1.28fr);
            align-items: stretch;
        }

        .upload-fields {
            display: grid;
            align-content: start;
            gap: 12px;
            min-width: 0;
        }

        .folder-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
            padding: 14px;
            border-bottom: 1px solid var(--line);
        }

        .folder-list {
            display: grid;
            gap: 5px;
            padding: 12px;
        }

        .folder-link,
        .folder-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            min-height: 42px;
            padding: 9px 10px;
            border-radius: 8px;
        }

        .folder-link {
            color: var(--text);
        }

        .folder-link:hover,
        .folder-link.active {
            background: var(--surface-muted);
            color: var(--primary-dark);
        }

        .folder-link.active {
            box-shadow: inset 3px 0 0 var(--primary);
        }

        .folder-row {
            padding: 0;
        }

        .folder-row .folder-link {
            min-width: 0;
        }

        .folder-actions {
            display: flex;
            gap: 5px;
            align-items: center;
            justify-content: flex-end;
        }

        .folder-actions > form {
            display: flex;
        }

        .folder-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .folder-count {
            color: var(--muted);
            font-size: 13px;
        }

        .folder-delete {
            min-width: 36px;
            min-height: 36px;
            padding: 6px 8px;
        }

        .folder-action-button {
            min-height: 36px;
            padding: 6px 8px;
            font-size: 12px;
        }

        .folder-action-menu {
            width: 70px;
        }

        .folder-action-panel {
            width: min(360px, calc(100vw - 36px));
        }

        .folder-action-panel form {
            display: grid;
        }

        .folder-rename-form {
            gap: 8px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--line);
        }

        .folder-rename-form label {
            display: grid;
            gap: 4px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
        }

        .folder-rename-form .field {
            min-height: 34px;
            padding: 7px 8px;
            font-size: 12px;
        }

        .folder-rename-form .button {
            min-height: 32px;
            justify-content: center;
            padding: 6px 8px;
            font-size: 12px;
        }

        .dropzone {
            display: flex;
            min-height: 176px;
            align-items: center;
            justify-content: center;
            min-width: 0;
            padding: 22px;
            border: 1px dashed #aab4c5;
            border-radius: 8px;
            background: var(--surface-subtle);
            cursor: pointer;
            transition: border-color 160ms ease, background 160ms ease;
        }

        .dropzone:hover {
            border-color: var(--primary);
            background: #eaeefc;
        }

        .dropzone-inner {
            display: grid;
            grid-template-columns: 54px minmax(0, 1fr);
            gap: 14px;
            align-items: center;
            width: min(560px, 100%);
        }

        .dropzone-icon {
            display: grid;
            width: 54px;
            height: 54px;
            place-items: center;
            border-radius: 8px;
            background: var(--success-soft);
            color: var(--primary-dark);
            font-size: 22px;
            font-weight: 700;
        }

        .dropzone-copy {
            display: grid;
            gap: 5px;
            min-width: 0;
        }

        .dropzone-copy strong,
        .dropzone-copy span {
            display: block;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dropzone-copy strong {
            color: var(--ink);
            font-size: 16px;
        }

        .dropzone-copy span {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.4;
        }

        .dropzone input {
            grid-column: 1 / -1;
            width: 100%;
            max-width: 100%;
            margin-top: 4px;
        }

        .dropzone input::file-selector-button {
            min-height: 36px;
            margin-right: 10px;
            padding: 8px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: var(--text);
            font-weight: 700;
            cursor: pointer;
        }

        .hint {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .upload-actions {
            justify-content: space-between;
            margin-top: 0;
        }

        .upload-target {
            margin-bottom: 0;
        }

        .upload-meta {
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface-subtle);
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .upload-footer {
            display: flex;
            grid-column: 1 / -1;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .upload-footer .upload-meta {
            flex: 1;
        }

        .upload-progress {
            display: grid;
            grid-column: 1 / -1;
            gap: 10px;
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background:
                linear-gradient(135deg, rgb(79 70 229 / 6%) 0%, rgb(6 182 212 / 6%) 100%),
                var(--surface);
            box-shadow: 0 8px 24px rgb(79 70 229 / 8%);
        }

        .upload-progress[hidden] {
            display: none;
        }

        .upload-progress-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: var(--primary-dark);
            font-size: 13px;
        }

        .upload-progress-track {
            position: relative;
            height: 10px;
            overflow: hidden;
            border-radius: 999px;
            background: rgb(15 23 42 / 6%);
        }

        .upload-progress-track span {
            position: relative;
            display: block;
            width: 0;
            height: 100%;
            border-radius: inherit;
            background:
                linear-gradient(90deg, var(--primary), var(--accent));
            transition: width 220ms ease;
            box-shadow: 0 0 12px rgb(79 70 229 / 30%);
        }

        .upload-progress-track span::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background-image: linear-gradient(135deg, rgb(255 255 255 / 30%) 25%, transparent 25%, transparent 50%, rgb(255 255 255 / 30%) 50%, rgb(255 255 255 / 30%) 75%, transparent 75%, transparent);
            background-size: 24px 24px;
            animation: upload-stripes 800ms linear infinite;
            opacity: 0.7;
        }

        .upload-progress p {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.4;
        }

        /* === Topbar v2 === */
        .topbar-v2 {
            padding: 14px 18px;
            border: 1px solid rgb(223 227 237 / 75%);
            border-radius: 14px;
            background:
                linear-gradient(135deg, rgb(255 255 255 / 95%) 0%, rgb(247 249 253 / 90%) 100%);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .topbar-v2 .brand-mark {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: #fff;
            box-shadow: 0 8px 18px rgb(79 70 229 / 24%);
        }

        .topbar-v2 .nav-actions {
            gap: 8px;
        }

        .user-chip-v2 {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px 6px 6px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .user-chip-avatar {
            display: grid;
            place-items: center;
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0;
        }

        .user-chip-name {
            color: var(--ink);
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .nav-button {
            min-height: 38px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
        }

        .nav-button svg {
            width: 16px;
            height: 16px;
        }

        .nav-button-logout {
            color: var(--danger);
        }

        .nav-button-logout:hover {
            color: var(--danger);
            border-color: #fbb9c4;
            background: var(--danger-soft);
        }

        /* === Home v2 === */
        .home-hero {
            position: relative;
            overflow: hidden;
            margin: 12px 0 32px;
            padding: 36px 40px 40px;
            border: 1px solid var(--line);
            border-radius: 24px;
            background:
                linear-gradient(135deg, rgb(255 255 255 / 88%) 0%, rgb(247 249 253 / 80%) 100%),
                var(--surface);
            box-shadow: 0 30px 70px rgb(15 23 42 / 8%);
        }

        .home-hero-orb {
            position: absolute;
            display: block;
            border-radius: 50%;
            filter: blur(70px);
            opacity: 0.5;
            pointer-events: none;
            z-index: 0;
            animation: home-orb-drift 18s ease-in-out infinite alternate;
        }

        .home-hero-orb-a {
            top: -60px;
            right: -40px;
            width: 360px;
            height: 360px;
            background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
        }

        .home-hero-orb-b {
            bottom: -80px;
            left: -60px;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, var(--accent) 0%, transparent 70%);
            animation-delay: -6s;
        }

        @keyframes home-orb-drift {
            from { transform: translate(0, 0) scale(1); }
            to { transform: translate(20px, -16px) scale(1.08); }
        }

        .home-hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(420px, 0.95fr);
            gap: 36px;
            align-items: center;
        }

        .home-hero-copy {
            min-width: 0;
        }

        .home-hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--surface);
            color: var(--ink);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.02em;
            box-shadow: var(--shadow-soft);
        }

        .home-hero-eyebrow-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 10px var(--success);
            animation: home-pulse 2s ease-in-out infinite;
        }

        @keyframes home-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.85); }
        }

        .home-hero-title {
            margin: 22px 0 0;
            color: var(--ink);
            font-size: 56px;
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -0.025em;
        }

        .home-hero-accent {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .home-hero-lead {
            margin: 22px 0 0;
            max-width: 560px;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.55;
        }

        .home-hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .button-large {
            min-height: 52px;
            padding: 12px 22px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
        }

        .button-large svg {
            width: 18px;
            height: 18px;
        }

        .home-hero-trust {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, auto));
            gap: 36px;
            margin-top: 36px;
            padding-top: 28px;
            border-top: 1px solid var(--line);
            justify-content: start;
        }

        .home-hero-trust strong {
            display: block;
            color: var(--ink);
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1;
        }

        .home-hero-trust span {
            display: block;
            margin-top: 6px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .home-hero-preview {
            position: relative;
            min-width: 0;
        }

        .home-preview-window {
            position: relative;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--surface);
            box-shadow: 0 30px 80px rgb(15 23 42 / 16%);
            overflow: hidden;
            transform: rotate(0.5deg);
        }

        .home-preview-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 14px;
            border-bottom: 1px solid var(--line);
            background: var(--surface-subtle);
        }

        .home-preview-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
        }

        .home-preview-dot-r { background: #f87171; }
        .home-preview-dot-y { background: #fbbf24; }
        .home-preview-dot-g { background: #34d399; }

        .home-preview-url {
            margin-left: 12px;
            padding: 4px 10px;
            border-radius: 999px;
            background: var(--surface-muted);
            color: var(--muted);
            font-family: Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 11px;
        }

        .home-preview-body {
            display: grid;
            grid-template-columns: 130px minmax(0, 1fr);
            gap: 0;
        }

        .home-preview-folders {
            display: grid;
            gap: 4px;
            padding: 14px 12px;
            border-right: 1px solid var(--line);
            background: var(--surface-subtle);
        }

        .home-preview-folders-head {
            margin-bottom: 4px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .home-preview-folder {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 6px;
            padding: 7px 9px;
            border-radius: 8px;
            color: var(--text);
            font-size: 12px;
            font-weight: 500;
        }

        .home-preview-folder b {
            color: var(--muted);
            font-weight: 600;
            font-size: 11px;
        }

        .home-preview-folder.is-active {
            background: var(--primary-soft);
            color: var(--primary-dark);
            box-shadow: inset 3px 0 0 var(--primary);
        }

        .home-preview-folder.is-active b {
            color: var(--primary-dark);
        }

        .home-preview-main {
            display: grid;
            gap: 10px;
            padding: 14px;
        }

        .home-preview-upload {
            display: grid;
            grid-template-columns: 36px 1fr;
            gap: 10px;
            align-items: center;
            padding: 12px;
            border: 1.5px dashed var(--line-strong);
            border-radius: 10px;
            background: var(--surface-subtle);
        }

        .home-preview-upload-icon {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--primary-soft);
            color: var(--primary);
        }

        .home-preview-upload-icon svg {
            width: 18px;
            height: 18px;
        }

        .home-preview-upload strong {
            display: block;
            color: var(--ink);
            font-size: 13px;
        }

        .home-preview-upload small {
            display: block;
            margin-top: 2px;
            color: var(--muted);
            font-size: 11px;
        }

        .home-preview-files {
            display: grid;
            gap: 6px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .home-preview-files li {
            display: grid;
            grid-template-columns: 36px minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            padding: 8px 10px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface);
        }

        .home-preview-file-badge {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        .home-preview-badge-pdf {
            background: #fef0e8;
            color: #c2410c;
        }

        .home-preview-badge-img {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .home-preview-badge-zip {
            background: #efe9fc;
            color: #6a3fc7;
        }

        .home-preview-files strong {
            display: block;
            color: var(--ink);
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .home-preview-files small {
            display: block;
            margin-top: 2px;
            color: var(--muted);
            font-size: 10px;
        }

        .home-preview-status {
            color: var(--line-strong);
            font-size: 14px;
        }

        .home-preview-status-ok {
            color: var(--success);
        }

        .home-preview-floater {
            position: absolute;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--surface);
            box-shadow: 0 14px 32px rgb(15 23 42 / 12%);
            z-index: 2;
        }

        .home-preview-floater strong {
            display: block;
            color: var(--ink);
            font-size: 12px;
            font-weight: 700;
        }

        .home-preview-floater small {
            display: block;
            margin-top: 2px;
            color: var(--muted);
            font-size: 11px;
        }

        .home-preview-floater-icon {
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: var(--primary-soft);
            color: var(--primary);
        }

        .home-preview-floater-icon-cyan {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .home-preview-floater-icon svg {
            width: 16px;
            height: 16px;
        }

        .home-preview-floater-share {
            top: 22%;
            left: -22px;
            animation: home-floater-1 5s ease-in-out infinite alternate;
        }

        .home-preview-floater-tg {
            bottom: 14%;
            right: -22px;
            animation: home-floater-2 6s ease-in-out infinite alternate;
        }

        @keyframes home-floater-1 {
            from { transform: translateY(0); }
            to { transform: translateY(-12px); }
        }

        @keyframes home-floater-2 {
            from { transform: translateY(0); }
            to { transform: translateY(10px); }
        }

        .home-section-head {
            text-align: center;
            margin: 0 auto 32px;
            max-width: 720px;
        }

        .home-section-head h2 {
            margin: 10px 0 0;
            color: var(--ink);
            font-size: 36px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.02em;
        }

        .home-features {
            margin: 64px 0;
        }

        .home-feature-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .home-feature {
            display: grid;
            gap: 10px;
            padding: 24px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
            transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
        }

        .home-feature:hover {
            transform: translateY(-4px);
            border-color: var(--line-strong);
            box-shadow: 0 22px 48px rgb(15 23 42 / 12%);
        }

        .home-feature-icon {
            display: grid;
            place-items: center;
            width: 48px;
            height: 48px;
            border-radius: 13px;
            margin-bottom: 4px;
        }

        .home-feature-icon svg {
            width: 22px;
            height: 22px;
        }

        .home-feature-icon-indigo { background: var(--primary-soft); color: var(--primary); }
        .home-feature-icon-cyan { background: var(--accent-soft); color: var(--accent); }
        .home-feature-icon-pink { background: #fdeaf3; color: #db2777; }
        .home-feature-icon-teal { background: #d1fae5; color: var(--success); }
        .home-feature-icon-amber { background: #fef3c7; color: #b45309; }
        .home-feature-icon-violet { background: #ede9fe; color: #7c3aed; }

        .home-feature strong {
            color: var(--ink);
            font-size: 18px;
            font-weight: 700;
        }

        .home-feature p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.55;
        }

        .home-steps {
            margin: 64px 0;
        }

        .home-steps-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .home-step {
            position: relative;
            display: grid;
            gap: 10px;
            padding: 26px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .home-step-num {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            margin-bottom: 4px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: #fff;
            font-size: 16px;
            font-weight: 800;
            box-shadow: 0 8px 18px rgb(79 70 229 / 22%);
        }

        .home-step strong {
            color: var(--ink);
            font-size: 17px;
            font-weight: 700;
        }

        .home-step p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.55;
        }

        .home-cta {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 28px;
            align-items: center;
            margin: 64px 0 24px;
            padding: 40px 44px;
            border-radius: 24px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: #fff;
            box-shadow: 0 30px 70px rgb(79 70 229 / 28%);
        }

        .home-cta-orb {
            position: absolute;
            display: block;
            border-radius: 50%;
            filter: blur(50px);
            opacity: 0.45;
            pointer-events: none;
            background: #fff;
        }

        .home-cta-orb-a { width: 220px; height: 220px; top: -80px; right: 10%; }
        .home-cta-orb-b { width: 180px; height: 180px; bottom: -70px; left: 30%; }

        .home-cta-text {
            position: relative;
            z-index: 1;
        }

        .home-cta-eyebrow {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 999px;
            background: rgb(255 255 255 / 18%);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .home-cta h2 {
            margin: 12px 0 0;
            color: #fff;
            font-size: 30px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .home-cta-actions {
            position: relative;
            z-index: 1;
        }

        .home-cta-actions .button {
            background: #fff;
            color: var(--primary-dark);
            box-shadow: 0 14px 32px rgb(15 23 42 / 18%);
        }

        .home-cta-actions .button:hover {
            background: var(--primary-soft);
            color: var(--primary-dark);
            transform: translateY(-2px);
        }

        @media (max-width: 980px) {
            .home-hero {
                padding: 28px 22px 30px;
                border-radius: 20px;
            }

            .home-hero-grid {
                grid-template-columns: 1fr;
                gap: 32px;
            }

            .home-hero-title {
                font-size: 40px;
            }

            .home-hero-trust {
                gap: 24px;
            }

            .home-feature-grid,
            .home-steps-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .home-section-head h2 {
                font-size: 28px;
            }

            .home-cta {
                grid-template-columns: 1fr;
                padding: 30px 24px;
                text-align: center;
            }

            .home-cta h2 {
                font-size: 24px;
            }

            .home-preview-floater {
                display: none;
            }
        }

        @media (max-width: 620px) {
            .home-hero-title {
                font-size: 32px;
            }

            .home-hero-trust {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 16px;
            }

            .home-hero-trust strong {
                font-size: 20px;
            }

            .home-feature-grid,
            .home-steps-grid {
                grid-template-columns: 1fr;
            }

            .home-features,
            .home-steps {
                margin: 44px 0;
            }
        }

        /* === Sidebar / Folders v2 === */
        .sidebar-panel {
            overflow: hidden;
            border-radius: 14px;
        }

        .sidebar-header {
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            padding: 16px 16px 14px;
            border-bottom: 1px solid var(--line);
            background:
                radial-gradient(120% 140% at 100% 0%, rgb(6 182 212 / 5%) 0%, transparent 60%),
                radial-gradient(120% 140% at 0% 100%, rgb(79 70 229 / 6%) 0%, transparent 60%),
                var(--surface);
        }

        .sidebar-header h2 {
            margin: 2px 0 0;
            color: var(--ink);
            font-size: 18px;
            line-height: 1.1;
        }

        .sidebar-header p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .sidebar-header-icon {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            border-radius: 11px;
            background: var(--primary-soft);
            color: var(--primary);
        }

        .sidebar-header-icon svg {
            width: 20px;
            height: 20px;
        }

        .folder-form-v2 {
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
            padding: 12px;
            border-bottom: 1px solid var(--line);
            background: var(--surface-subtle);
        }

        .folder-form-v2 .field {
            min-height: 40px;
            padding: 9px 12px;
            border-radius: 10px;
            font-size: 13px;
        }

        .folder-form-v2 .button {
            min-height: 40px;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
        }

        .folder-list-v2 {
            display: grid;
            gap: 4px;
            padding: 10px;
        }

        .folder-list-v2 .folder-link {
            grid-template-columns: 28px minmax(0, 1fr) auto;
            gap: 10px;
            padding: 8px 10px;
            min-height: 40px;
            border-radius: 10px;
            color: var(--text);
            transition: background 140ms ease, color 140ms ease;
        }

        .folder-list-v2 .folder-link:hover {
            background: var(--primary-soft);
            color: var(--primary-dark);
            box-shadow: none;
        }

        .folder-list-v2 .folder-link.active {
            background: var(--primary-soft);
            color: var(--primary-dark);
            box-shadow: inset 3px 0 0 var(--primary);
        }

        .folder-link-icon {
            display: grid;
            place-items: center;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: var(--surface-muted);
            color: var(--muted);
            transition: background 140ms ease, color 140ms ease;
        }

        .folder-link-icon svg {
            width: 16px;
            height: 16px;
        }

        .folder-list-v2 .folder-link:hover .folder-link-icon,
        .folder-list-v2 .folder-link.active .folder-link-icon {
            background: #fff;
            color: var(--primary);
        }

        .folder-list-v2 .folder-count {
            min-width: 28px;
            padding: 2px 8px;
            border-radius: 999px;
            background: var(--surface-muted);
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        .folder-list-v2 .folder-link:hover .folder-count,
        .folder-list-v2 .folder-link.active .folder-count {
            background: #fff;
            color: var(--primary-dark);
        }

        .folder-list-v2 .folder-row {
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 6px;
            align-items: center;
        }

        @media (max-width: 720px) {
            .topbar-v2 {
                padding: 12px;
            }

            .nav-button {
                flex: 1;
            }

            .user-chip-v2 {
                order: -1;
            }
        }

        /* === Stats v2 === */
        .stats-v2 .stat {
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: auto;
            padding: 16px 18px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
            transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
        }

        .stats-v2 .stat::before {
            display: none;
        }

        .stats-v2 .stat:hover {
            transform: translateY(-2px);
            border-color: var(--line-strong);
            box-shadow: 0 14px 32px rgb(15 23 42 / 10%);
        }

        .stats-v2 .stat-icon {
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--surface-muted);
            color: var(--primary);
        }

        .stats-v2 .stat-icon svg {
            width: 22px;
            height: 22px;
        }

        .stats-v2 .stat-body {
            display: grid;
            gap: 2px;
            min-width: 0;
        }

        .stats-v2 .stat-body strong {
            display: block;
            margin: 0;
            color: var(--ink);
            font-size: 24px;
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -0.01em;
        }

        .stats-v2 .stat-body span {
            display: block;
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .stats-v2 .stat-primary .stat-icon {
            background: var(--primary-soft);
            color: var(--primary);
        }

        .stats-v2 .stat-accent .stat-icon {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .stats-v2 .stat-violet .stat-icon {
            background: #fdeaf3;
            color: #db2777;
        }

        .stats-v2 .stat-telegram .stat-icon {
            background: #e0f2fe;
            color: #0284c7;
        }

        .stats-v2 .stat-ink .stat-icon {
            background: #e2e8f0;
            color: #334155;
        }

        @media (max-width: 720px) {
            .stats-v2 .stat {
                padding: 12px 14px;
            }

            .stats-v2 .stat-icon {
                width: 38px;
                height: 38px;
            }

            .stats-v2 .stat-body strong {
                font-size: 20px;
            }
        }

        /* === Upload v2 === */
        .upload-panel-v2 {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--line);
            background:
                radial-gradient(120% 140% at 100% 0%, rgb(6 182 212 / 6%) 0%, transparent 55%),
                radial-gradient(120% 140% at 0% 100%, rgb(79 70 229 / 8%) 0%, transparent 55%),
                var(--surface);
        }

        .upload-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            padding: 22px 22px 18px;
            border-bottom: 1px solid var(--line);
        }

        .upload-hero-text {
            min-width: 0;
            max-width: 640px;
        }

        .upload-hero-text h2 {
            margin: 6px 0 0;
            color: var(--ink);
            font-size: 24px;
            line-height: 1.2;
        }

        .upload-hero-text p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .upload-hero-chips {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .upload-chip {
            display: grid;
            gap: 4px;
            min-width: 120px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .upload-chip span {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .upload-chip strong {
            color: var(--ink);
            font-size: 18px;
            line-height: 1.1;
        }

        .upload-chip-info {
            border-color: #a8e7f1;
            background: var(--accent-soft);
        }

        .upload-chip-info strong {
            color: var(--accent);
        }

        .upload-form-v2 {
            display: grid;
            gap: 14px;
            padding: 22px;
        }

        .dropzone-v2 {
            position: relative;
            display: grid;
            grid-template-columns: 72px minmax(0, 1fr);
            gap: 18px;
            align-items: center;
            padding: 26px;
            border: 2px dashed #aab4c5;
            border-radius: 14px;
            background:
                radial-gradient(80% 120% at 50% 0%, rgb(6 182 212 / 4%) 0%, transparent 60%),
                var(--surface-subtle);
            cursor: pointer;
            transition: border-color 180ms ease, background 180ms ease, transform 180ms ease, box-shadow 180ms ease;
        }

        .dropzone-v2:hover {
            border-color: var(--primary);
            background: #eaeefc;
        }

        .dropzone-v2.is-dragover {
            border-color: var(--accent);
            background: var(--accent-soft);
            transform: translateY(-1px);
            box-shadow: 0 14px 36px rgb(6 182 212 / 12%);
        }

        .dropzone-v2 input[type="file"] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .dropzone-v2-graphic {
            display: grid;
            place-items: center;
            width: 72px;
            height: 72px;
            border-radius: 16px;
            background: #fff;
            color: var(--primary);
            box-shadow: var(--shadow-soft);
        }

        .dropzone-v2-graphic svg {
            width: 38px;
            height: 38px;
        }

        .dropzone-v2.is-dragover .dropzone-v2-graphic {
            color: var(--accent);
        }

        .dropzone-v2-body {
            display: grid;
            gap: 4px;
            min-width: 0;
        }

        .dropzone-v2-body strong {
            color: var(--ink);
            font-size: 17px;
        }

        .dropzone-v2-body span {
            color: var(--muted);
            font-size: 14px;
        }

        .dropzone-v2-body em {
            color: var(--primary-dark);
            font-style: normal;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .dropzone-v2-body small {
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.45;
        }

        .upload-selected {
            display: grid;
            gap: 10px;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--surface);
        }

        .upload-selected[hidden] {
            display: none;
        }

        .upload-selected-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .upload-selected-summary {
            display: flex;
            align-items: baseline;
            gap: 10px;
            color: var(--ink);
        }

        .upload-selected-summary strong {
            font-size: 14px;
        }

        .upload-selected-summary span {
            color: var(--muted);
            font-size: 13px;
        }

        .upload-selected-clear {
            color: var(--danger);
            font-weight: 600;
        }

        .upload-selected-list {
            display: grid;
            gap: 6px;
            margin: 0;
            padding: 0;
            list-style: none;
            max-height: 280px;
            overflow-y: auto;
        }

        .upload-selected-item {
            position: relative;
            display: grid;
            grid-template-columns: 36px minmax(0, 1fr) auto auto auto;
            gap: 10px;
            align-items: center;
            padding: 10px 12px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface-subtle);
            overflow: hidden;
            transition: border-color 200ms ease, background 200ms ease, box-shadow 200ms ease;
        }

        .upload-selected-item[data-state="queued"] {
            opacity: 0.85;
        }

        .upload-selected-item[data-state="uploading"],
        .upload-selected-item[data-state="processing"] {
            border-color: var(--primary);
            background: var(--primary-soft);
            box-shadow: 0 6px 18px rgb(79 70 229 / 10%);
        }

        .upload-selected-item[data-state="done"] {
            border-color: #a7e0c8;
            background: var(--success-soft);
        }

        .upload-selected-item[data-state="error"] {
            border-color: #fbb9c4;
            background: var(--danger-soft);
        }

        .upload-selected-state {
            display: none;
            place-items: center;
            width: 24px;
            height: 24px;
        }

        .upload-selected-state svg {
            width: 18px;
            height: 18px;
            display: none;
        }

        .upload-selected-item[data-state="uploading"] .upload-selected-state,
        .upload-selected-item[data-state="processing"] .upload-selected-state,
        .upload-selected-item[data-state="done"] .upload-selected-state,
        .upload-selected-item[data-state="error"] .upload-selected-state {
            display: grid;
        }

        .upload-selected-item[data-state="uploading"] .upload-state-spinner,
        .upload-selected-item[data-state="processing"] .upload-state-spinner {
            display: block;
            color: var(--primary);
            animation: upload-spinner 900ms linear infinite;
        }

        .upload-selected-item[data-state="done"] .upload-state-check {
            display: block;
            color: var(--success);
            animation: upload-pop 240ms ease;
        }

        .upload-selected-item[data-state="error"] .upload-state-error {
            display: block;
            color: var(--danger);
        }

        .upload-selected-item[data-state="uploading"] .upload-selected-remove,
        .upload-selected-item[data-state="processing"] .upload-selected-remove,
        .upload-selected-item[data-state="queued"] .upload-selected-remove,
        .upload-selected-item[data-state="done"] .upload-selected-remove {
            visibility: hidden;
            pointer-events: none;
        }

        .upload-selected-item[data-state="uploading"] .upload-selected-status,
        .upload-selected-item[data-state="processing"] .upload-selected-status {
            color: var(--primary-dark);
            font-weight: 600;
        }

        .upload-selected-item[data-state="done"] .upload-selected-status {
            color: var(--success);
            font-weight: 600;
        }

        .upload-selected-item[data-state="error"] .upload-selected-status {
            color: var(--danger);
            font-weight: 600;
        }

        .upload-item-progress {
            position: absolute;
            inset: auto 0 0 0;
            display: block;
            height: 3px;
            background: rgb(15 23 42 / 5%);
            overflow: hidden;
        }

        .upload-item-progress-bar {
            display: block;
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transition: width 200ms ease;
        }

        .upload-selected-item[data-state="done"] .upload-item-progress-bar {
            background: var(--success);
        }

        .upload-selected-item[data-state="error"] .upload-item-progress-bar {
            background: var(--danger);
        }

        .upload-selected-item[data-state="uploading"] .upload-item-progress-bar,
        .upload-selected-item[data-state="processing"] .upload-item-progress-bar {
            background-size: 24px 24px;
            background-image:
                linear-gradient(90deg, var(--primary), var(--accent)),
                linear-gradient(135deg, rgb(255 255 255 / 25%) 25%, transparent 25%, transparent 50%, rgb(255 255 255 / 25%) 50%, rgb(255 255 255 / 25%) 75%, transparent 75%, transparent);
            background-blend-mode: overlay;
            animation: upload-stripes 800ms linear infinite;
        }

        @keyframes upload-spinner {
            to { transform: rotate(360deg); }
        }

        @keyframes upload-pop {
            0% { transform: scale(0.6); opacity: 0; }
            60% { transform: scale(1.15); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes upload-stripes {
            from { background-position: 0 0, 0 0; }
            to { background-position: 0 0, 24px 0; }
        }

        .upload-form-v2.is-uploading,
        .is-uploading .upload-controls,
        .is-uploading .dropzone-v2,
        .is-uploading .upload-selected-clear {
            pointer-events: none;
        }

        .is-uploading .dropzone-v2,
        .is-uploading .upload-controls {
            opacity: 0.55;
        }

        .upload-selected-icon {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #fff;
            color: var(--primary-dark);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .upload-selected-name {
            display: grid;
            gap: 2px;
            min-width: 0;
        }

        .upload-selected-name strong {
            color: var(--ink);
            font-size: 13px;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .upload-selected-name span {
            color: var(--muted);
            font-size: 11px;
        }

        .upload-selected-size {
            color: var(--muted);
            font-size: 12px;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .upload-selected-remove {
            display: grid;
            place-items: center;
            width: 28px;
            height: 28px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: var(--muted);
            cursor: pointer;
            transition: color 140ms ease, border-color 140ms ease, background 140ms ease;
        }

        .upload-selected-remove:hover {
            color: var(--danger);
            border-color: #edc2ca;
            background: var(--danger-soft);
        }

        .upload-controls {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .upload-control {
            position: relative;
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr) 22px;
            gap: 12px;
            align-items: center;
            padding: 10px 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--surface);
            cursor: pointer;
            transition: border-color 160ms ease, box-shadow 160ms ease, background 160ms ease, transform 160ms ease;
        }

        .upload-control:hover {
            border-color: var(--line-strong);
            background: var(--surface-subtle);
        }

        .upload-control:focus-within {
            border-color: var(--primary);
            background: var(--surface);
            box-shadow: 0 0 0 4px rgb(79 70 229 / 12%);
            transform: translateY(-1px);
        }

        .upload-control-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 11px;
            background: var(--primary-soft);
            color: var(--primary);
            transition: transform 160ms ease;
        }

        .upload-control-storage .upload-control-icon {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .upload-control:hover .upload-control-icon {
            transform: scale(1.04);
        }

        .upload-control-icon svg {
            width: 22px;
            height: 22px;
        }

        .upload-control-body {
            display: grid;
            gap: 1px;
            min-width: 0;
        }

        .upload-control-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .upload-control-value {
            display: block;
            min-width: 0;
            color: var(--ink);
            font-size: 14px;
            font-weight: 600;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .upload-control-select {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
            color: var(--ink);
            font: inherit;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        .upload-control-select:focus {
            outline: none;
        }

        .upload-control-select option {
            padding: 8px 12px;
            background: var(--surface);
            color: var(--ink);
            font-size: 14px;
            font-weight: 500;
        }

        .upload-control-select option:checked,
        .upload-control-select option:hover,
        .upload-control-select option:focus {
            background: var(--primary-soft);
            color: var(--primary-dark);
        }

        .upload-control-select option[disabled] {
            color: var(--muted);
        }

        .upload-control-chevron {
            display: grid;
            place-items: center;
            width: 22px;
            height: 22px;
            color: var(--muted);
            pointer-events: none;
            transition: color 160ms ease, transform 160ms ease;
        }

        .upload-control-chevron svg {
            width: 16px;
            height: 16px;
        }

        .upload-control:focus-within .upload-control-chevron {
            color: var(--primary);
            transform: translateY(1px);
        }

        .upload-warning {
            padding: 10px 12px;
            border: 1px solid #edd49a;
            border-radius: 10px;
            background: #fdf6e3;
            color: #6a4a07;
            font-size: 13px;
            line-height: 1.45;
        }

        @media (max-width: 720px) {
            .upload-hero {
                padding: 18px 16px 14px;
            }

            .upload-form-v2 {
                padding: 16px;
            }

            .dropzone-v2 {
                grid-template-columns: 1fr;
                justify-items: center;
                padding: 22px 18px;
                text-align: center;
            }

            .dropzone-v2-body {
                justify-items: center;
                text-align: center;
            }

            .upload-controls {
                grid-template-columns: 1fr;
            }
        }

        .filters {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 170px auto;
            gap: 10px;
            padding: 14px;
            border-bottom: 1px solid var(--line);
            background: var(--surface-subtle);
        }

        .file-view-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-bottom: 1px solid var(--line);
        }

        .file-view-bar span {
            color: var(--muted);
            font-size: 13px;
        }

        .view-toggle {
            display: flex;
            gap: 6px;
        }

        .view-toggle .button {
            min-height: 34px;
            padding: 7px 10px;
            font-size: 13px;
        }

        .view-toggle .active {
            border-color: #a7e0c8;
            background: var(--success-soft);
            color: var(--primary-dark);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .compact-file-table {
            min-width: 900px;
        }

        .compact-file-table th,
        .compact-file-table td {
            padding: 9px 12px;
            font-size: 13px;
        }

        .compact-file-table th {
            background: var(--surface-subtle);
            font-size: 11px;
        }

        .compact-file-table tbody tr {
            transition: background 140ms ease;
        }

        .compact-file-table tbody tr:hover {
            background: #fafbfd;
        }

        .file-table-name {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .file-table-title {
            min-width: 0;
        }

        .file-table-title strong,
        .file-table-title span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-table-title strong {
            max-width: 260px;
            font-size: 13px;
        }

        .file-table-title span {
            max-width: 220px;
            color: var(--muted);
            font-size: 12px;
        }

        .compact-file-table .file-icon {
            width: 42px;
            min-height: 30px;
            padding: 5px 6px;
            border-radius: 6px;
            font-size: 11px;
        }

        .file-row-actions {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
            min-width: 92px;
        }

        .file-row-actions form {
            display: flex;
        }

        .file-row-actions .button,
        .file-tile-actions .button {
            min-height: 32px;
            padding: 6px 9px;
            font-size: 12px;
        }

        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 12px;
            padding: 14px;
        }

        .file-tile {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 0;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            transition: border-color 140ms ease, box-shadow 140ms ease, background 140ms ease;
        }

        .file-tile:hover {
            border-color: var(--line-strong);
            background: #fafbfd;
            box-shadow: var(--shadow-soft);
        }

        .file-tile-preview {
            display: block;
            width: 100%;
            aspect-ratio: 16 / 10;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface-subtle);
        }

        .file-tile-preview img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 180ms ease;
        }

        .file-tile-preview:hover img {
            transform: scale(1.03);
        }

        .file-tile-preview-empty {
            display: grid;
            place-items: center;
            border-style: dashed;
            background: #fafbfd;
            color: var(--muted);
        }

        .file-tile-preview-empty span {
            display: inline-flex;
            min-width: 52px;
            min-height: 36px;
            align-items: center;
            justify-content: center;
            padding: 7px 10px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: var(--accent);
            font-size: 12px;
            font-weight: 800;
        }

        .file-tile-head {
            display: grid;
            grid-template-columns: 46px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
        }

        .file-tile-title {
            min-width: 0;
        }

        .file-tile-title strong,
        .file-tile-title span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-tile-title strong {
            font-size: 14px;
        }

        .file-tile-title span,
        .file-tile-meta {
            color: var(--muted);
            font-size: 12px;
        }

        .file-tile-meta {
            display: grid;
            gap: 4px;
        }

        .file-tile-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: auto;
        }

        .file-tile-actions form {
            display: flex;
        }

        .file-tile-actions .button {
            white-space: nowrap;
        }

        .file-action-menu {
            position: relative;
            width: 94px;
            margin-left: auto;
        }

        .file-tile-actions .file-action-menu {
            width: 100%;
            margin-left: 0;
        }

        .file-action-menu summary {
            list-style: none;
        }

        .file-action-menu summary::-webkit-details-marker {
            display: none;
        }

        .action-menu-trigger {
            width: 100%;
            justify-content: center;
            cursor: pointer;
        }

        .action-menu-trigger::after {
            content: "›";
            margin-left: 6px;
            transform: rotate(90deg);
            font-size: 14px;
        }

        .file-action-menu[open] .action-menu-trigger {
            border-color: #bbe5d5;
            background: var(--success-soft);
            color: var(--primary-dark);
        }

        .file-action-panel {
            display: grid;
            gap: 8px;
            position: fixed;
            top: var(--action-panel-top, 92px);
            left: var(--action-panel-left, 18px);
            z-index: 80;
            width: min(390px, calc(100vw - 36px));
            max-height: calc(100vh - 116px);
            overflow: auto;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            box-shadow: var(--shadow);
            text-align: left;
        }

        .action-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            min-height: 34px;
            margin: -4px -4px 2px;
            padding: 6px 8px;
            border-radius: 7px;
            background: var(--surface-subtle);
            color: var(--text);
            cursor: grab;
            touch-action: none;
            user-select: none;
        }

        .action-panel-head:active {
            cursor: grabbing;
        }

        .action-panel-head strong {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 12px;
        }

        .action-panel-close {
            display: grid;
            flex: 0 0 auto;
            width: 26px;
            height: 26px;
            place-items: center;
            border: 1px solid var(--line);
            border-radius: 7px;
            background: #fff;
            color: var(--muted);
            font-weight: 900;
            cursor: pointer;
        }

        .action-panel-close:hover {
            border-color: #f0bbc3;
            background: var(--danger-soft);
            color: var(--danger);
        }

        .action-menu-links {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 5px;
        }

        .action-menu-links form {
            display: grid;
        }

        .action-line {
            display: flex;
            width: 100%;
            min-height: 30px;
            align-items: center;
            justify-content: center;
            padding: 6px 8px;
            border: 1px solid var(--line);
            border-radius: 7px;
            background: var(--surface-subtle);
            color: var(--text);
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .action-line:hover {
            border-color: var(--line-strong);
            background: var(--surface-muted);
        }

        .action-line.accent {
            border-color: #a8e7f1;
            background: var(--accent-soft);
            color: var(--accent);
        }

        .action-line.danger {
            border-color: #f0bbc3;
            background: var(--danger-soft);
            color: var(--danger);
        }

        .share-settings {
            display: grid;
            gap: 8px;
            padding-top: 10px;
            border-top: 1px solid var(--line);
        }

        .share-settings-head,
        .share-inline-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }

        .share-settings-head strong {
            font-size: 12px;
        }

        .share-settings-head span {
            padding: 3px 7px;
            border-radius: 999px;
            background: var(--surface-muted);
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
        }

        .share-settings.is-enabled .share-settings-head span {
            background: var(--success-soft);
            color: var(--primary-dark);
        }

        .share-link-field,
        .share-limit-grid label {
            display: grid;
            gap: 4px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
        }

        .share-link-field .field,
        .share-limit-grid .field {
            min-height: 32px;
            padding: 6px 8px;
            font-size: 12px;
        }

        .share-enabled {
            display: grid;
            gap: 8px;
        }

        .share-inline-actions {
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .share-inline-button,
        .share-save-button,
        .share-inline-actions .button {
            min-height: 30px;
            padding: 5px 8px;
            font-size: 11px;
        }

        .share-save-button {
            width: 100%;
            justify-content: center;
        }

        .share-limit-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
        }

        .share-usage,
        .share-message {
            margin: 0;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.35;
        }

        .share-message {
            min-height: 15px;
            color: var(--success);
            font-weight: 700;
        }

        .share-message.is-error {
            color: var(--danger);
        }

        @media (max-width: 720px) {
            .file-action-panel {
                top: var(--action-panel-top, auto);
                left: var(--action-panel-left, 12px);
                right: auto;
                bottom: auto;
                width: min(390px, calc(100vw - 24px));
                max-height: min(560px, calc(100vh - 24px));
            }

            .action-menu-links,
            .share-limit-grid {
                grid-template-columns: 1fr;
            }
        }

        .file-list {
            display: grid;
            gap: 10px;
            padding: 14px;
        }

        .file-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px 16px;
            align-items: center;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
        }

        .file-card:hover {
            border-color: var(--line-strong);
            background: #fafbfd;
        }

        .file-card-main {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .file-icon {
            display: grid;
            flex: 0 0 auto;
            width: 54px;
            min-height: 42px;
            place-items: center;
            padding: 7px 8px;
            border-radius: 8px;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: 12px;
            font-weight: 700;
            text-align: center;
        }

        .file-details {
            display: grid;
            gap: 5px;
            min-width: 0;
        }

        .file-details strong {
            display: block;
            min-width: 0;
            overflow: hidden;
            color: var(--text);
            font-size: 15px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-details span {
            color: var(--muted);
            font-size: 13px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .file-card-actions .button {
            min-height: 38px;
            padding: 8px 12px;
        }

        .file-card-meta {
            display: grid;
            grid-column: 1 / -1;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .file-meta-item {
            min-width: 0;
            padding: 9px 10px;
            border-radius: 8px;
            background: var(--surface-muted);
        }

        .file-meta-item span {
            display: block;
            color: var(--muted);
            font-size: 12px;
        }

        .file-meta-item strong {
            display: block;
            min-width: 0;
            margin-top: 4px;
            overflow: hidden;
            color: var(--text);
            font-size: 13px;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        table {
            width: 100%;
            min-width: 840px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: middle;
        }

        th {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        td {
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .file-name {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            font-weight: 700;
        }

        .file-type {
            flex: 0 0 auto;
            min-width: 46px;
            padding: 5px 7px;
        }

        .file-title {
            overflow-wrap: anywhere;
        }

        .muted {
            color: var(--muted);
        }

        .actions {
            justify-content: flex-end;
            white-space: nowrap;
        }

        .empty {
            padding: 34px 18px;
            color: var(--muted);
            text-align: center;
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 18px;
            border-top: 1px solid var(--line);
            background: var(--surface-subtle);
            color: var(--muted);
            font-size: 14px;
        }

        .pagination .disabled {
            opacity: 0.45;
            cursor: default;
        }

        .preview-shell {
            display: grid;
            gap: 18px;
        }

        .preview-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid var(--line);
        }

        .preview-title {
            min-width: 0;
        }

        .preview-title h1 {
            margin: 0;
            overflow-wrap: anywhere;
            font-size: 20px;
        }

        .preview-title p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .preview-frame {
            padding: 18px;
        }

        .preview-image {
            display: block;
            width: auto;
            max-width: 100%;
            max-height: 72vh;
            margin: 0 auto;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
        }

        .text-preview {
            max-height: 72vh;
            margin: 0;
            overflow: auto;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #101820;
            color: #edf4ef;
            font-family: Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 13px;
            line-height: 1.55;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .truncated-note {
            margin: 0 0 12px;
            padding: 10px 12px;
            border: 1px solid #ecd493;
            border-radius: 8px;
            background: #fff8df;
            color: #70520d;
            font-size: 14px;
        }

        .public-share-shell,
        .public-folder-shell {
            max-width: 1120px;
            margin: 0 auto;
        }

        .share-badge {
            display: inline-flex;
            width: fit-content;
            margin-bottom: 8px;
            padding: 5px 8px;
            border: 1px solid #bbe5d5;
            border-radius: 999px;
            background: var(--success-soft);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 800;
        }

        .share-toolbar-actions {
            display: flex;
            flex: 0 0 auto;
            gap: 8px;
        }

        .share-empty {
            border: 1px dashed var(--line-strong);
            border-radius: 8px;
            background: var(--surface-subtle);
        }

        .public-folder-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .public-folder-header h2 {
            margin-top: 0;
        }

        .public-share-table {
            min-width: 780px;
        }

        @media (max-width: 900px) {
            .hero,
            .workspace,
            .settings-grid,
            .settings-compact-grid,
            .landing-hero-grid {
                grid-template-columns: 1fr;
            }

            .landing-metrics,
            .landing-benefits,
            .landing-flow,
            .landing-audience {
                grid-template-columns: 1fr;
            }

            .snapshot-body {
                grid-template-columns: 1fr;
            }

            .snapshot-folders {
                border-right: 0;
                border-bottom: 1px solid var(--line);
            }

            .welcome-grid,
            .welcome-summary {
                grid-template-columns: 1fr;
            }

            .guide-steps {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .compact-settings-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .premium-upload-form {
                grid-template-columns: 1fr;
            }

            .upload-footer {
                align-items: stretch;
                flex-direction: column;
            }

            .hero {
                min-height: auto;
            }

            .stats,
            .feature-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 620px) {
            .page {
                width: min(100% - 20px, 1180px);
                padding-top: 12px;
                padding-bottom: 44px;
            }

            .site-nav,
            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .landing-nav {
                gap: 12px;
                padding-bottom: 12px;
            }

            .landing-nav .brand {
                width: 100%;
            }

            .landing-nav .brand-mark {
                width: 40px;
                height: 40px;
            }

            .landing-nav .brand strong {
                font-size: 21px;
            }

            .landing-nav .brand p {
                font-size: 12px;
            }

            .mobile-cta-bar {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin: 0 0 12px;
                padding: 0;
            }

            .mobile-cta-bar .button {
                width: auto;
                min-height: 40px;
                justify-content: center;
                padding: 8px 13px;
                white-space: nowrap;
            }

            .mobile-cta-bar .button.secondary {
                width: auto;
            }

            .mobile-cta-bar .button:first-child:last-child {
                width: auto;
            }

            .landing-nav .nav-actions {
                display: none;
            }

            .topbar {
                padding: 10px;
            }

            .hero h1 {
                font-size: 36px;
            }

            .welcome-hero {
                min-height: auto;
                padding: 26px 0 24px;
            }

            .welcome-copy h1 {
                font-size: 38px;
            }

            .welcome-copy p:not(.eyebrow) {
                font-size: 16px;
            }

            .landing-panel {
                gap: 14px;
                padding: 16px;
                border-radius: 8px;
                background: #fff;
            }

            .landing-hero-copy h1 {
                margin-top: 12px;
                font-size: 31px;
                line-height: 1.1;
            }

            .landing-hero-copy p {
                margin-top: 12px;
                font-size: 15px;
                line-height: 1.5;
            }

            .landing-label {
                min-height: 28px;
                padding: 5px 9px;
                font-size: 11px;
                line-height: 1.25;
            }

            .landing-actions {
                display: none;
            }

            .landing-points {
                gap: 6px;
                margin-top: 12px;
            }

            .landing-points span {
                min-height: 30px;
                padding: 6px 9px;
                font-size: 12px;
            }

            .landing-section-head h2,
            .landing-final h2 {
                font-size: 24px;
                line-height: 1.18;
            }

            .landing-section {
                gap: 12px;
                padding-top: 22px;
            }

            .landing-benefits article,
            .landing-audience article,
            .landing-flow div {
                padding: 14px;
            }

            .landing-benefits strong,
            .landing-flow strong,
            .landing-audience strong {
                font-size: 16px;
            }

            .landing-final {
                align-items: stretch;
                flex-direction: column;
                margin-top: 22px;
                padding: 16px;
            }

            .landing-snapshot {
                border-color: #dfe3ed;
                box-shadow: none;
            }

            .snapshot-toolbar {
                align-items: center;
                flex-direction: row;
                gap: 8px;
                padding: 11px 12px;
            }

            .snapshot-body {
                min-height: auto;
            }

            .snapshot-folders {
                display: flex;
                overflow-x: auto;
                gap: 8px;
                padding: 10px;
                scrollbar-width: none;
            }

            .snapshot-folders::-webkit-scrollbar {
                display: none;
            }

            .snapshot-folders strong {
                display: none;
            }

            .snapshot-folders span {
                flex: 0 0 auto;
                min-height: 32px;
                padding: 7px 10px;
                border: 1px solid var(--line);
                background: #fff;
                white-space: nowrap;
            }

            .snapshot-main {
                gap: 8px;
                padding: 10px;
            }

            .snapshot-upload {
                display: none;
            }

            .snapshot-files div {
                align-items: flex-start;
                grid-template-columns: 40px minmax(0, 1fr);
                gap: 8px;
                min-height: auto;
                padding: 9px;
            }

            .snapshot-files strong {
                white-space: normal;
                line-height: 1.25;
            }

            .snapshot-files small {
                grid-column: 2;
                white-space: normal;
            }

            .upload-panel-head {
                align-items: stretch;
                flex-direction: column;
            }

            .upload-limit {
                text-align: left;
            }

            .stats,
            .filters,
            .guide-steps,
            .compact-settings-form,
            .welcome-grid,
            .welcome-summary,
            .landing-metrics,
            .landing-benefits,
            .landing-flow,
            .landing-audience,
            .feature-strip,
            .folder-form,
            .file-card-meta {
                grid-template-columns: 1fr;
            }

            .landing-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .landing-metrics div {
                padding: 12px;
            }

            .landing-metrics strong,
            .landing-metrics span {
                white-space: normal;
            }

            .landing-metrics strong {
                font-size: 20px;
            }

            .landing-metrics span {
                margin-top: 6px;
                font-size: 12px;
                line-height: 1.25;
            }

            .button {
                width: auto;
            }

            .auth-panel .button,
            .upload-actions .button,
            .landing-final .button,
            .pagination .button {
                width: 100%;
            }

            .landing-points span {
                width: auto;
                max-width: 100%;
                justify-content: flex-start;
            }

            .snapshot-files small {
                justify-self: start;
            }

            .button.link {
                width: auto;
            }

            .nav-actions,
            .actions,
            .auth-row,
            .pagination,
            .preview-toolbar,
            .upload-actions,
            .upload-footer,
            .file-view-bar {
                align-items: stretch;
                flex-direction: column;
                width: 100%;
            }

            .view-toggle {
                width: 100%;
            }

            .guide-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .view-toggle .button {
                flex: 1;
            }

            .actions {
                justify-content: flex-start;
            }

            .file-card {
                grid-template-columns: 1fr;
            }

            .file-card-actions {
                justify-content: flex-start;
            }

            .table-wrap {
                overflow: visible;
            }

            .compact-file-table {
                width: 100%;
                min-width: 0;
                border-collapse: separate;
                border-spacing: 0;
            }

            .compact-file-table thead {
                display: none;
            }

            .compact-file-table tbody {
                display: grid;
                gap: 8px;
                padding: 10px;
            }

            .compact-file-table tbody tr {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 7px 10px;
                padding: 10px;
                border: 1px solid var(--line);
                border-radius: 8px;
                background: #fff;
                box-shadow: var(--shadow-soft);
            }

            .compact-file-table th,
            .compact-file-table td {
                display: block;
                padding: 0;
                border: 0;
                font-size: 12px;
            }

            .compact-file-table td:first-child {
                grid-column: 1;
                grid-row: 1;
                min-width: 0;
            }

            .compact-file-table td:nth-child(2),
            .compact-file-table td:nth-child(3),
            .compact-file-table td:nth-child(4),
            .compact-file-table td:nth-child(5) {
                grid-column: 1 / -1;
                color: var(--muted);
                font-size: 12px;
                line-height: 1.3;
            }

            .compact-file-table td:nth-child(2)::before {
                content: "Папка: ";
                color: var(--text);
                font-weight: 800;
            }

            .compact-file-table td:nth-child(3)::before {
                content: "Сховище: ";
                color: var(--text);
                font-weight: 800;
            }

            .compact-file-table td:nth-child(4)::before {
                content: "Розмір: ";
                color: var(--text);
                font-weight: 800;
            }

            .compact-file-table td:nth-child(5)::before {
                content: "Дата: ";
                color: var(--text);
                font-weight: 800;
            }

            .compact-file-table td:last-child {
                grid-column: 2;
                grid-row: 1;
                align-self: start;
            }

            .file-table-name {
                gap: 7px;
                align-items: flex-start;
            }

            .file-table-title strong,
            .file-table-title span {
                max-width: none;
                white-space: normal;
                line-height: 1.28;
            }

            .file-table-title strong {
                font-size: 13px;
            }

            .file-row-actions {
                justify-content: flex-end;
                min-width: 0;
            }

            .file-row-actions .file-action-menu,
            .file-tile-actions .file-action-menu {
                width: 72px;
            }

            .file-row-actions .button,
            .file-tile-actions .button,
            .share-inline-actions .button,
            .share-inline-button {
                width: auto;
            }

            .file-grid {
                grid-template-columns: 1fr;
                gap: 8px;
                padding: 10px;
            }

            .file-tile {
                gap: 8px;
                padding: 10px;
                box-shadow: var(--shadow-soft);
            }

            .file-tile-preview {
                aspect-ratio: 16 / 9;
            }

            .file-tile-head {
                gap: 8px;
            }

            .file-tile-title strong,
            .file-tile-title span {
                white-space: normal;
                line-height: 1.28;
            }

            .file-tile-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }

            .file-tile-meta span {
                padding: 4px 7px;
                border-radius: 999px;
                background: var(--surface-muted);
                color: var(--muted);
                font-size: 11px;
            }

            .file-tile-actions {
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        @yield('content')
    </main>
    <script>
        (() => {
            const prefix = '+380';
            const maxLocalLength = 9;

            const onlyDigits = (value) => (value || '').replace(/\D/g, '');

            const localDigits = (value) => {
                let digits = onlyDigits(value);

                if (digits.startsWith('380')) {
                    digits = digits.slice(3);
                }

                if (digits.length > maxLocalLength && digits.startsWith('0')) {
                    digits = digits.slice(1);
                }

                return digits.slice(0, maxLocalLength);
            };

            const formatLocal = (digits) => {
                const parts = [];

                if (digits.length > 0) {
                    parts.push(digits.slice(0, 2));
                }

                if (digits.length > 2) {
                    parts.push(digits.slice(2, 5));
                }

                let formatted = parts.join(' ');

                if (digits.length > 5) {
                    formatted += '-' + digits.slice(5, 7);
                }

                if (digits.length > 7) {
                    formatted += '-' + digits.slice(7, 9);
                }

                return formatted;
            };

            document.querySelectorAll('[data-phone-mask]').forEach((wrapper) => {
                const fieldGroup = wrapper.closest('.field-group') || document;
                const fullInput = fieldGroup.querySelector('[data-phone-full]');
                const localInput = wrapper.querySelector('[data-phone-local]');

                if (! fullInput || ! localInput) {
                    return;
                }

                const sync = (value) => {
                    const digits = localDigits(value);
                    localInput.value = formatLocal(digits);
                    fullInput.value = digits.length === maxLocalLength ? prefix + digits : '';
                };

                sync(fullInput.value || localInput.value);

                localInput.addEventListener('input', () => sync(localInput.value));
                localInput.form?.addEventListener('submit', () => sync(localInput.value));
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
