<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'FileProxy')</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f6f7f4;
            --surface: #ffffff;
            --surface-muted: #edf1ee;
            --line: #dce3df;
            --text: #17211b;
            --muted: #66756c;
            --primary: #0f8b6f;
            --primary-dark: #0b6f59;
            --accent: #2d6cdf;
            --danger: #c43d3d;
            --ink: #1f2f43;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
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
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 24px 0 40px;
        }

        .site-nav,
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .site-nav {
            padding-bottom: 28px;
        }

        .topbar {
            padding-bottom: 22px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .brand-mark {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            font-weight: 700;
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
            font-size: 14px;
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

        .file-type {
            border-radius: 6px;
            background: #eaf1ff;
            color: var(--accent);
            font-size: 12px;
            font-weight: 700;
            text-align: center;
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
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
            border: 1px solid #b8cbf5;
            border-radius: 8px;
            background: #f4f8ff;
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
            background: #fff;
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
            border: 1px solid #8fcdb2;
            border-radius: 8px;
            background: #eaf8f1;
            color: #145c42;
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
            min-height: 42px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: var(--text);
            padding: 10px 12px;
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
            border-color: #9abbb0;
            box-shadow: 0 0 0 3px rgb(15 139 111 / 10%);
        }

        .phone-prefix {
            display: grid;
            place-items: center;
            border-right: 1px solid var(--line);
            background: #f7faf8;
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
            border: 1px solid #aad7c2;
            border-radius: 8px;
            background: #e9f8f0;
            color: #145c42;
            font-size: 14px;
            line-height: 1.45;
        }

        .status::before {
            content: "OK";
            flex: 0 0 auto;
            padding: 2px 6px;
            border-radius: 999px;
            background: #ccebdd;
            color: #145c42;
            font-size: 11px;
            font-weight: 700;
        }

        .errors {
            display: grid;
            gap: 8px;
            margin: 0 0 18px;
            padding: 14px 16px;
            border: 1px solid #efb7b7;
            border-radius: 8px;
            background: #fff4f4;
            color: #812828;
            font-size: 14px;
            line-height: 1.45;
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
            background: #f6cece;
            color: #812828;
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
            min-height: 42px;
            padding: 10px 14px;
            border: 1px solid transparent;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .button:hover {
            background: var(--primary-dark);
        }

        .button.secondary {
            background: #fff;
            border-color: var(--line);
            color: var(--text);
            font-weight: 600;
        }

        .button.secondary:hover {
            background: var(--surface-muted);
        }

        .button.accent {
            background: #fff;
            border-color: #b8cbf5;
            color: var(--accent);
        }

        .button.accent:hover {
            background: #edf3ff;
        }

        .button.danger {
            background: #fff;
            border-color: #efc7c7;
            color: var(--danger);
        }

        .button.danger:hover {
            background: #fff1f1;
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
            gap: 12px;
            margin-bottom: 18px;
        }

        .stat {
            min-height: 94px;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
        }

        .stat span {
            display: block;
            color: var(--muted);
            font-size: 13px;
        }

        .stat strong {
            display: block;
            margin-top: 10px;
            font-size: 26px;
            line-height: 1;
        }

        .workspace {
            display: grid;
            grid-template-columns: minmax(260px, 360px) minmax(0, 1fr);
            gap: 18px;
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
            gap: 14px;
            align-items: start;
        }

        .settings-guide {
            margin-bottom: 14px;
        }

        .panel-header.compact {
            padding: 14px 16px 0;
        }

        .guide-steps {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            padding: 14px 16px;
        }

        .guide-steps div {
            min-width: 0;
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fbfcfb;
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
        }

        .settings-note {
            margin: 0 16px 16px;
            padding: 10px 12px;
            border: 1px solid #b8cbf5;
            border-radius: 8px;
            background: #f4f8ff;
            color: #264b84;
            font-size: 13px;
            line-height: 1.45;
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

        .compact-settings-form .field {
            min-height: 38px;
            padding: 8px 10px;
        }

        .compact-settings-form .button {
            min-height: 38px;
            padding: 8px 12px;
        }

        .compact-checkbox {
            min-height: 38px;
            align-items: center;
            padding-bottom: 2px;
            white-space: nowrap;
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

        .settings-table th,
        .settings-table td {
            padding: 9px 12px;
            font-size: 13px;
        }

        .settings-table th {
            font-size: 11px;
        }

        .settings-table td strong,
        .settings-table td > span {
            display: block;
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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

        .table-actions .button {
            min-height: 30px;
            padding: 5px 8px;
            font-size: 12px;
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
            background: #dff1ea;
            color: #145c42;
        }

        .badge.danger {
            background: #f8dede;
            color: #812828;
        }

        .badge.accent {
            background: #eaf1ff;
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

        .upload-form {
            display: grid;
            gap: 14px;
            padding: 18px;
        }

        .folder-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
            padding: 18px;
            border-bottom: 1px solid var(--line);
        }

        .folder-list {
            display: grid;
            gap: 6px;
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
        }

        .folder-row {
            padding: 0;
        }

        .folder-row .folder-link {
            min-width: 0;
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

        .dropzone {
            display: flex;
            min-height: 190px;
            align-items: center;
            justify-content: center;
            padding: 24px;
            border: 1px dashed #8fa99d;
            border-radius: 8px;
            background: linear-gradient(180deg, #f7fbf9 0%, var(--surface-muted) 100%);
            text-align: center;
            cursor: pointer;
            transition: border-color 160ms ease, background 160ms ease;
        }

        .dropzone:hover {
            border-color: var(--primary);
            background: #edf7f3;
        }

        .dropzone-inner {
            display: grid;
            justify-items: center;
            gap: 10px;
        }

        .dropzone-icon {
            display: grid;
            width: 48px;
            height: 48px;
            place-items: center;
            border-radius: 8px;
            background: #dff1ea;
            color: var(--primary-dark);
            font-size: 22px;
            font-weight: 700;
        }

        .dropzone input {
            width: 100%;
            max-width: 280px;
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
            background: #fbfcfb;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .filters {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 170px auto;
            gap: 10px;
            padding: 18px;
            border-bottom: 1px solid var(--line);
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
            border-color: #9abbb0;
            background: #eaf8f1;
            color: var(--primary-dark);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .compact-file-table {
            min-width: 880px;
        }

        .compact-file-table th,
        .compact-file-table td {
            padding: 8px 10px;
            font-size: 13px;
        }

        .compact-file-table th {
            font-size: 11px;
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
            white-space: nowrap;
        }

        .file-row-actions .button,
        .file-tile-actions .button {
            min-height: 32px;
            padding: 6px 9px;
            font-size: 12px;
        }

        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 10px;
            padding: 14px;
        }

        .file-tile {
            display: grid;
            gap: 10px;
            min-width: 0;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
        }

        .file-tile:hover {
            border-color: #c7d3cd;
            background: #fbfdfc;
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
            border-color: #c7d3cd;
            background: #fbfdfc;
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
            background: #eaf1ff;
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

        @media (max-width: 900px) {
            .hero,
            .workspace,
            .settings-grid {
                grid-template-columns: 1fr;
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
                padding-top: 16px;
            }

            .site-nav,
            .topbar {
                align-items: flex-start;
                flex-direction: column;
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

            .stats,
            .filters,
            .guide-steps,
            .compact-settings-form,
            .welcome-grid,
            .welcome-summary,
            .feature-strip,
            .folder-form,
            .file-card-meta {
                grid-template-columns: 1fr;
            }

            .button {
                width: 100%;
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
            .file-view-bar {
                align-items: stretch;
                flex-direction: column;
                width: 100%;
            }

            .view-toggle {
                width: 100%;
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
