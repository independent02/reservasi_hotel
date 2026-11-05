<!doctype html>
<html lang="{{ app()->getLocale() }}" class="payments-portal-root">
<head>
    @include('partials.preferences-head')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Verifikasi Pembayaran') }} — Hotel HAR Official</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .payments-portal {
            --portal-ink: #f8fafc;
            --portal-muted: #aab7ce;
            --portal-line: rgb(255 255 255 / 10%);
            min-height: 100vh;
            width: 100%;
            padding: 1rem 1rem 4rem;
            overflow-x: clip;
            isolation: isolate;
            position: relative;
            color: var(--portal-ink);
            background-color: #0b1224;
            background-image: linear-gradient(135deg, #020617 0%, #14234a 48%, #0b1224 100%);
            font-family: "Plus Jakarta Sans", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .payments-portal *,
        .payments-portal *::before,
        .payments-portal *::after { box-sizing: border-box; }
        .payments-portal button,
        .payments-portal input,
        .payments-portal select { font: inherit; }
        .payments-portal button,
        .payments-portal a { -webkit-tap-highlight-color: transparent; }
        .payments-glow {
            position: absolute;
            z-index: 0;
            width: 28rem;
            height: 28rem;
            border-radius: 50%;
            background: rgb(59 130 246 / 15%);
            filter: blur(100px);
            pointer-events: none;
        }
        .payments-glow-one { top: 4rem; left: -17rem; }
        .payments-glow-two { right: -18rem; bottom: -13rem; background: rgb(30 64 175 / 20%); }
        .payments-shell { position: relative; z-index: 1; width: min(100%, 1440px); margin: 0 auto; }
        .payments-topbar {
            position: sticky;
            top: 1rem;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            min-height: 3.5rem;
            padding: .65rem 1rem;
            border: 1px solid var(--portal-line);
            border-radius: 1rem;
            background: linear-gradient(115deg, rgb(30 41 68 / 94%), rgb(15 23 42 / 88%));
            box-shadow: 0 16px 36px rgb(2 6 23 / 42%), 0 2px 0 rgb(255 255 255 / 3%), inset 0 1px 0 rgb(255 255 255 / 10%);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }
        .payments-brand {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            color: #f8fafc;
            font-size: .88rem;
            font-weight: 800;
            letter-spacing: -.035em;
            text-decoration: none;
        }
        .payments-brand img { width: 2rem; height: 2rem; object-fit: contain; }
        .payments-account { display: flex; align-items: center; gap: .65rem; }
        .payments-account > .navbar-preferences {
            flex: 0 0 auto;
            gap: .25rem;
            padding: .1rem;
            border: 1px solid rgb(148 163 184 / 18%);
            border-radius: .85rem;
            background: rgb(15 23 42 / 42%);
        }
        .payments-account .navbar-locale-switch {
            gap: .05rem;
            padding: .08rem .12rem;
            border-color: rgb(148 163 184 / 22%);
            background: rgb(15 23 42 / 58%);
        }
        .payments-account .navbar-locale-icon { width: .78rem; height: .78rem; }
        .payments-account .navbar-locale-switch button {
            min-width: 1.65rem;
            min-height: 1.5rem;
            padding: .2rem .3rem;
            font-size: .58rem;
        }
        .payments-account .navbar-theme-toggle {
            width: 1.8rem;
            height: 1.8rem;
            border-color: rgb(148 163 184 / 22%);
            border-radius: .65rem;
            background: rgb(15 23 42 / 58%);
        }
        .payments-account .navbar-theme-toggle svg { width: .9rem; height: .9rem; }
        .payments-bell-wrap { position: relative; }
        .payments-bell {
            position: relative;
            display: grid;
            width: 2.45rem;
            height: 2.45rem;
            place-items: center;
            border: 1px solid rgb(255 255 255 / 15%);
            border-radius: 50%;
            color: #dbeafe;
            background: linear-gradient(145deg, rgb(71 85 105 / 80%), rgb(30 41 59 / 95%));
            box-shadow: 0 5px 0 rgb(2 6 23 / 55%), 0 10px 18px rgb(2 6 23 / 32%), inset 0 1px 0 rgb(255 255 255 / 15%);
            cursor: pointer;
            transition: transform .18s ease, filter .18s ease;
        }
        .payments-bell:hover { transform: translateY(-1px); filter: brightness(1.12); }
        .payments-bell:active { transform: translateY(2px); box-shadow: 0 2px 0 rgb(2 6 23 / 55%); }
        .payments-bell svg { width: 1.1rem; height: 1.1rem; fill: none; stroke: currentColor; stroke-width: 1.7; }
        .payments-badge {
            position: absolute;
            top: -.3rem;
            right: -.3rem;
            min-width: 1.05rem;
            padding: .08rem .28rem;
            border: 1px solid rgb(15 23 42 / 75%);
            border-radius: 999px;
            color: #0f172a;
            background: #f59e0b;
            box-shadow: 0 3px 8px rgb(245 158 11 / 30%);
            font-size: .62rem;
            font-weight: 800;
            line-height: 1.25;
            text-align: center;
            animation: payments-pulse 1.9s ease-in-out infinite;
        }
        @keyframes payments-pulse { 50% { box-shadow: 0 0 0 4px rgb(245 158 11 / 13%), 0 3px 8px rgb(245 158 11 / 30%); } }
        .payments-admin-mark {
            display: grid;
            width: 2rem;
            height: 2rem;
            place-items: center;
            border: 1px solid rgb(255 255 255 / 15%);
            border-radius: .65rem;
            color: #dbeafe;
            background: linear-gradient(145deg, #475569, #1e293b);
            font-size: .7rem;
            font-weight: 800;
        }
        .payments-admin-name { display: grid; gap: .08rem; }
        .payments-admin-name strong { font-size: .7rem; line-height: 1.2; }
        .payments-admin-name span { color: var(--portal-muted); font-size: .6rem; }
        .payments-logout { margin: 0; }
        .payments-logout button,
        .payments-filter-submit,
        .payments-page-link,
        .payments-action {
            border: 1px solid rgb(255 255 255 / 12%);
            color: #e2e8f0;
            background: linear-gradient(145deg, #334155, #1e293b);
            box-shadow: 0 3px 0 rgb(2 6 23 / 52%), inset 0 1px 0 rgb(255 255 255 / 8%);
            text-decoration: none;
            cursor: pointer;
            transition: transform .16s ease, filter .16s ease, box-shadow .16s ease;
        }
        .payments-logout button { padding: .55rem .85rem; border-radius: .7rem; font-size: .68rem; font-weight: 700; }
        .payments-logout button:hover,
        .payments-filter-submit:hover,
        .payments-page-link:hover:not([aria-disabled="true"]),
        .payments-action:hover { transform: translateY(-1px); filter: brightness(1.13); }
        .payments-logout button:active,
        .payments-filter-submit:active,
        .payments-page-link:active,
        .payments-action:active { transform: translateY(2px); box-shadow: 0 1px 0 rgb(2 6 23 / 52%); }
        .payments-popover {
            position: absolute;
            top: calc(100% + .8rem);
            right: -.6rem;
            z-index: 20;
            width: min(21rem, calc(100vw - 2rem));
            padding: 1rem;
            border: 1px solid rgb(255 255 255 / 15%);
            border-radius: 1rem;
            background: rgb(15 23 42 / 96%);
            box-shadow: 0 24px 55px rgb(2 6 23 / 58%), inset 0 1px 0 rgb(255 255 255 / 6%);
            backdrop-filter: blur(22px);
        }
        .payments-popover[hidden] { display: none; }
        .payments-popover::before {
            position: absolute;
            top: -.35rem;
            right: 1.35rem;
            width: .65rem;
            height: .65rem;
            border-top: 1px solid rgb(255 255 255 / 15%);
            border-left: 1px solid rgb(255 255 255 / 15%);
            background: #0f172a;
            content: "";
            transform: rotate(45deg);
        }
        .payments-popover-head { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; margin-bottom: .65rem; }
        .payments-popover-head strong { font-size: .78rem; }
        .payments-popover-head span { color: var(--portal-muted); font-size: .58rem; white-space: nowrap; }
        .payments-notice { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; padding: .65rem .15rem; border-top: 1px solid rgb(255 255 255 / 7%); }
        .payments-notice-main { min-width: 0; }
        .payments-notice-main strong,
        .payments-notice-main span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .payments-notice-main strong { font-size: .67rem; }
        .payments-notice-main span { margin-top: .2rem; color: #b9c5d8; font-size: .57rem; }
        .payments-notice time { padding-top: .08rem; color: var(--portal-muted); font-size: .53rem; white-space: nowrap; }
        .payments-notice-empty { padding: .8rem .1rem; color: var(--portal-muted); font-size: .66rem; }
        .payments-notice-cta {
            display: block;
            margin-top: .55rem;
            padding: .65rem .8rem;
            border: 1px solid rgb(191 219 254 / 25%);
            border-radius: .7rem;
            color: #f8fafc;
            background: linear-gradient(145deg, #475569, #29364e);
            box-shadow: 0 4px 0 rgb(2 6 23 / 65%), inset 0 1px 0 rgb(255 255 255 / 12%);
            font-size: .62rem;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
        }
        .payments-intro { padding: 2rem 0 1.25rem; }
        .payments-eyebrow { margin: 0 0 .45rem; color: #9db5df; font-size: .61rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        .payments-title { margin: 0; color: #f8fafc; font-size: clamp(1.55rem, 3.2vw, 2.45rem); font-weight: 800; letter-spacing: -.055em; line-height: 1.1; }
        .payments-subtitle { max-width: 45rem; margin: .65rem 0 0; color: #c3cee0; font-size: .76rem; line-height: 1.7; }
        .payments-filter-panel {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid var(--portal-line);
            border-radius: 1rem;
            background: linear-gradient(125deg, rgb(30 41 68 / 76%), rgb(15 23 42 / 67%));
            box-shadow: 0 12px 28px rgb(2 6 23 / 18%), inset 0 1px 0 rgb(255 255 255 / 7%);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }
        .payments-filter-form { display: flex; align-items: end; gap: .75rem; width: 100%; }
        .payments-field { display: grid; gap: .38rem; }
        .payments-field label { color: #aebbd0; font-size: .59rem; font-weight: 700; }
        .payments-search-field { width: min(100%, 26rem); }
        .payments-search-wrap { position: relative; }
        .payments-search-wrap svg {
            position: absolute;
            top: 50%;
            left: .8rem;
            width: .95rem;
            height: .95rem;
            fill: none;
            stroke: #94a3b8;
            stroke-width: 1.8;
            transform: translateY(-50%);
            pointer-events: none;
        }
        .payments-search,
        .payments-status-select {
            width: 100%;
            min-height: 2.65rem;
            border: 1px solid rgb(255 255 255 / 12%);
            border-radius: .7rem;
            outline: none;
            color: #f8fafc;
            background: linear-gradient(145deg, rgb(2 6 23 / 74%), rgb(15 23 42 / 72%));
            font-size: .67rem;
            box-shadow: inset 0 2px 5px rgb(2 6 23 / 35%), 0 1px 0 rgb(255 255 255 / 4%);
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }
        .payments-search { padding: .7rem .8rem .7rem 2.35rem; }
        .payments-search::placeholder { color: #8593aa; opacity: 1; }
        .payments-status-select {
            min-width: 12.25rem;
            padding: .7rem 2.35rem .7rem .8rem;
            appearance: none;
            background-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='m2 4 4 4 4-4' fill='none' stroke='%23cbd5e1' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5'/%3E%3C/svg%3E"),
                linear-gradient(145deg, rgb(2 6 23 / 74%), rgb(15 23 42 / 72%));
            background-repeat: no-repeat;
            background-position: right .9rem center, center;
            background-size: .75rem, auto;
        }
        .payments-status-select option { color: #f8fafc; background: #0f172a; }
        .payments-search:focus,
        .payments-status-select:focus { border-color: #60a5fa; box-shadow: 0 0 0 3px rgb(59 130 246 / 20%); }
        .payments-filter-submit { min-height: 2.65rem; padding: .65rem 1rem; border-radius: .7rem; font-size: .66rem; font-weight: 700; }
        .payments-flash { margin-top: 1rem; padding: .75rem .9rem; border: 1px solid rgb(16 185 129 / 25%); border-radius: .7rem; color: #a7f3d0; background: rgb(16 185 129 / 10%); font-size: .7rem; }
        .payments-flash-warning { border-color: rgb(245 158 11 / 28%); color: #fde68a; background: rgb(245 158 11 / 10%); }
        .payments-errors { margin: 1rem 0 0; padding: .75rem .9rem; border: 1px solid rgb(251 113 133 / 25%); border-radius: .7rem; color: #fecdd3; background: rgb(244 63 94 / 10%); font-size: .7rem; }
        .payments-errors ul { margin: .35rem 0 0; padding-left: 1.1rem; }
        .payments-table-card {
            margin-top: 1rem;
            padding: 1rem;
            border: 1px solid rgb(148 163 184 / 17%);
            border-radius: 1.25rem;
            background: linear-gradient(145deg, rgb(20 31 57 / 96%), rgb(10 18 37 / 95%));
            box-shadow: 0 28px 65px rgb(2 6 23 / 46%), 0 2px 0 rgb(255 255 255 / 3%), inset 0 1px 0 rgb(255 255 255 / 7%);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .payments-table-scroll { overflow-x: auto; border: 1px solid rgb(148 163 184 / 13%); border-radius: .9rem; background: rgb(2 6 23 / 15%); scrollbar-color: #475569 #0f172a; scrollbar-width: thin; }
        .payments-table { width: 100%; min-width: 1040px; border-collapse: collapse; text-align: left; }
        .payments-table thead { background: rgb(2 6 23 / 24%); }
        .payments-table th { padding: .72rem .65rem; color: #93a2bb; font-size: .54rem; font-weight: 800; letter-spacing: .075em; line-height: 1.35; text-transform: uppercase; white-space: nowrap; }
        .payments-table td { padding: .75rem .65rem; border-top: 1px solid rgb(255 255 255 / 7%); color: #dbe4f0; font-size: .62rem; vertical-align: middle; }
        .payments-table tbody tr { transition: background .16s ease; }
        .payments-table tbody tr:hover { background: linear-gradient(90deg, rgb(96 165 250 / 7%), rgb(255 255 255 / 2%)); }
        .payments-primary-cell { display: grid; gap: .23rem; min-width: 7rem; }
        .payments-primary-cell strong { color: #f8fafc; font-size: .66rem; font-weight: 750; }
        .payments-primary-cell span,
        .payments-secondary { color: #9eacc2; font-size: .55rem; }
        .payments-guest { display: grid; gap: .2rem; min-width: 8rem; }
        .payments-guest strong { color: #f1f5f9; font-size: .63rem; }
        .payments-guest span { color: #9eacc2; font-size: .55rem; }
        .payments-room { min-width: 8rem; color: #e2e8f0; font-weight: 650; }
        .payments-room span { display: block; margin-top: .2rem; color: #9eacc2; font-size: .54rem; font-weight: 500; }
        .payments-date { min-width: 8.7rem; color: #dbe4f0; font-size: .57rem; line-height: 1.6; white-space: nowrap; }
        .payments-date span { display: block; color: #9eacc2; }
        .payments-price { color: #f8fafc; font-size: .65rem; font-weight: 800; white-space: nowrap; }
        .payments-state { display: inline-flex; align-items: center; gap: .32rem; padding: .28rem .5rem; border: 1px solid; border-radius: 999px; font-size: .53rem; font-weight: 750; white-space: nowrap; }
        .payments-state::before { width: .3rem; height: .3rem; border-radius: 50%; background: currentColor; content: ""; }
        .payments-state-waiting { border-color: rgb(245 158 11 / 28%); color: #fbbf24; background: rgb(245 158 11 / 9%); }
        .payments-state-confirmed { border-color: rgb(52 211 153 / 26%); color: #6ee7b7; background: rgb(16 185 129 / 9%); }
        .payments-state-cancelled { border-color: rgb(251 113 133 / 28%); color: #fda4af; background: rgb(244 63 94 / 9%); }
        .payments-state-other { border-color: rgb(148 163 184 / 25%); color: #cbd5e1; background: rgb(148 163 184 / 8%); }
        .payments-actions { display: flex; align-items: center; justify-content: flex-end; gap: .3rem; white-space: nowrap; }
        .payments-action { display: inline-flex; min-height: 1.7rem; align-items: center; justify-content: center; padding: .3rem .47rem; border-radius: .45rem; font-size: .51rem; font-weight: 750; }
        .payments-action-proof { border-color: rgb(148 163 184 / 18%); background: linear-gradient(145deg, #475569, #29364e); }
        .payments-action-approve { border-color: rgb(16 185 129 / 30%); color: #a7f3d0; background: linear-gradient(145deg, #14532d, #064e3b); }
        .payments-action-cancel { border-color: rgb(245 158 11 / 28%); color: #fde68a; background: linear-gradient(145deg, #78350f, #451a03); }
        .payments-action-delete { border-color: rgb(251 113 133 / 24%); color: #fda4af; background: linear-gradient(145deg, #4c2432, #301826); }
        .payments-action:disabled { opacity: .45; cursor: not-allowed; transform: none; }
        .payments-action-form { display: inline-flex; margin: 0; }
        .payments-empty { padding: 3rem 1rem; color: var(--portal-muted); text-align: center; }
        .payments-empty strong { display: block; margin-bottom: .4rem; color: #e2e8f0; font-size: .8rem; }
        .payments-empty span { font-size: .65rem; }
        .payments-table-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem .1rem .05rem; }
        .payments-portal footer.payments-table-footer { padding: .85rem .1rem .05rem; color: inherit; background: transparent; }
        .payments-table-count { color: #9eacc2; font-size: .58rem; }
        .payments-pagination { display: flex; align-items: center; gap: .45rem; }
        .payments-page-link { display: inline-flex; min-height: 2rem; align-items: center; justify-content: center; padding: .45rem .65rem; border-radius: .55rem; font-size: .55rem; font-weight: 700; }
        .payments-page-link[aria-disabled="true"] { opacity: .42; cursor: not-allowed; }
        .payments-page-number { color: #cbd5e1; font-size: .56rem; font-weight: 650; white-space: nowrap; }
        .payments-dialog { width: min(36rem, calc(100vw - 2rem)); max-height: min(90vh, 46rem); padding: 0; overflow: auto; border: 1px solid rgb(255 255 255 / 15%); border-radius: 1.15rem; color: #f8fafc; background: #0f172a; box-shadow: 0 30px 90px rgb(0 0 0 / 60%); }
        .payments-dialog::backdrop { background: rgb(2 6 23 / 76%); backdrop-filter: blur(5px); }
        .payments-dialog-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.2rem 1.25rem; border-bottom: 1px solid var(--portal-line); }
        .payments-dialog-head p { margin: 0 0 .3rem; color: #93a2bb; font-size: .58rem; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }
        .payments-dialog-head h2 { margin: 0; font-size: 1.05rem; letter-spacing: -.03em; }
        .payments-dialog-close { width: 2rem; height: 2rem; border: 1px solid var(--portal-line); border-radius: .6rem; color: #e2e8f0; background: #1e293b; font-size: 1rem; cursor: pointer; }
        .payments-dialog-body { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; padding: 1.2rem 1.25rem; }
        .payments-dialog-item { min-width: 0; }
        .payments-dialog-item-wide { grid-column: 1 / -1; }
        .payments-dialog-item span { display: block; margin-bottom: .25rem; color: #93a2bb; font-size: .57rem; }
        .payments-dialog-item strong { display: block; overflow-wrap: anywhere; color: #e2e8f0; font-size: .68rem; font-weight: 650; }
        .payments-dialog-item-wide strong { white-space: pre-line; }
        .payments-dialog-foot { display: flex; justify-content: flex-end; padding: 0 1.25rem 1.2rem; }
        .payments-portal :focus-visible { outline: 2px solid #60a5fa; outline-offset: 3px; }

        @media (min-width: 768px) { .payments-portal { padding: 1.75rem 1.75rem 4rem; } .payments-intro { padding-top: 2.4rem; } .payments-table-card { padding: 1.25rem; } }
        @media (max-width: 640px) {
            .payments-portal { padding: .75rem .75rem 3rem; }
            .payments-topbar { top: .5rem; min-height: 3.2rem; padding: .5rem .65rem; }
            .payments-brand { gap: .4rem; font-size: .7rem; }
            .payments-brand img { width: 1.75rem; height: 1.75rem; }
            .payments-account { gap: .42rem; }
            .payments-admin-mark { display: none; }
            .payments-admin-name strong { max-width: 5rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .payments-logout button { padding: .5rem .6rem; font-size: .6rem; }
            .payments-account > .navbar-preferences { gap: .15rem; padding: .08rem; }
            .payments-account .navbar-locale-switch { gap: 0; padding: .06rem .08rem; }
            .payments-account .navbar-locale-switch button { min-width: 1.45rem; min-height: 1.35rem; padding-inline: .22rem; font-size: .54rem; }
            .payments-account .navbar-locale-icon { display: none; }
            .payments-account .navbar-theme-toggle { width: 1.6rem; height: 1.6rem; }
            .payments-popover {
                position: fixed;
                top: 4.5rem;
                right: .75rem;
                left: auto;
                width: min(20rem, calc(100vw - 1.5rem));
                max-height: calc(100vh - 5.25rem);
                overflow-y: auto;
            }
            .payments-popover::before { display: none; }
            .payments-intro { padding: 1.7rem 0 1rem; }
            .payments-subtitle { font-size: .68rem; }
            .payments-filter-panel { align-items: stretch; padding: .8rem; }
            .payments-filter-form { align-items: stretch; flex-direction: column; }
            .payments-search-field { width: 100%; }
            .payments-status-select { min-width: 0; }
            .payments-filter-submit { width: 100%; }
            .payments-table-card { padding: .65rem; border-radius: 1rem; }
            .payments-table-footer { align-items: flex-start; flex-direction: column; }
            .payments-pagination { width: 100%; justify-content: space-between; }
        }
        @media (prefers-reduced-motion: reduce) {
            .payments-portal *, .payments-portal *::before, .payments-portal *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }
        html:not(.dark).payments-portal-root,
        html:not(.dark) body.payments-portal {
            background-color: #f1f5f9 !important;
            background-image: linear-gradient(135deg, #f8fafc 0%, #eaf2ff 48%, #f1f5f9 100%) !important;
            color-scheme: light;
        }
        html:not(.dark) body.payments-portal {
            --portal-ink: #0f172a;
            --portal-muted: #64748b;
            --portal-line: rgb(148 163 184 / 35%);
            color: #0f172a;
        }
        html:not(.dark) .payments-glow { opacity: .35; }
        html:not(.dark) .payments-topbar,
        html:not(.dark) .payments-filter-panel,
        html:not(.dark) .payments-table-card {
            border-color: #dbe3ee;
            background: rgb(255 255 255 / 84%);
            box-shadow: 0 16px 36px rgb(15 23 42 / 9%), inset 0 1px 0 rgb(255 255 255 / 90%);
        }
        html:not(.dark) .payments-brand,
        html:not(.dark) .payments-admin-name strong,
        html:not(.dark) .payments-title,
        html:not(.dark) .payments-primary-cell strong,
        html:not(.dark) .payments-guest strong,
        html:not(.dark) .payments-room,
        html:not(.dark) .payments-price,
        html:not(.dark) .payments-empty strong,
        html:not(.dark) .payments-dialog,
        html:not(.dark) .payments-dialog-item strong {
            color: #0f172a;
        }
        html:not(.dark) .payments-admin-name span,
        html:not(.dark) .payments-eyebrow,
        html:not(.dark) .payments-subtitle,
        html:not(.dark) .payments-field label,
        html:not(.dark) .payments-table-count,
        html:not(.dark) .payments-page-number,
        html:not(.dark) .payments-primary-cell span,
        html:not(.dark) .payments-secondary,
        html:not(.dark) .payments-guest span,
        html:not(.dark) .payments-room span,
        html:not(.dark) .payments-date,
        html:not(.dark) .payments-table td,
        html:not(.dark) .payments-dialog-item span,
        html:not(.dark) .payments-dialog-head p {
            color: #475569;
        }
        html:not(.dark) .payments-eyebrow { color: #1d4ed8; }
        html:not(.dark) .payments-account > .navbar-preferences {
            border-color: #cbd5e1;
            background: #f1f5f9;
        }
        html:not(.dark) .payments-account .navbar-locale-switch {
            border-color: #cbd5e1;
            background: #e2e8f0;
        }
        html:not(.dark) .payments-account .navbar-locale-switch button { color: #475569; }
        html:not(.dark) .payments-account .navbar-locale-switch button[aria-pressed="true"] {
            background: #fff;
            color: #1e293b;
        }
        html:not(.dark) .payments-account .navbar-theme-toggle {
            border-color: #cbd5e1;
            background: #fff;
            color: #334155;
        }
        html:not(.dark) .payments-bell,
        html:not(.dark) .payments-admin-mark {
            border-color: #cbd5e1;
            color: #334155;
            background: linear-gradient(145deg, #fff, #e2e8f0);
            box-shadow: 0 4px 10px rgb(15 23 42 / 10%);
        }
        html:not(.dark) .payments-logout button,
        html:not(.dark) .payments-filter-submit,
        html:not(.dark) .payments-page-link,
        html:not(.dark) .payments-action {
            border-color: #cbd5e1;
            color: #1e293b;
            background: linear-gradient(145deg, #fff, #e2e8f0);
            box-shadow: 0 3px 0 rgb(148 163 184 / 30%), inset 0 1px 0 #fff;
        }
        html:not(.dark) .payments-popover {
            border-color: #dbe3ee;
            background: rgb(255 255 255 / 98%);
            box-shadow: 0 24px 55px rgb(15 23 42 / 16%);
        }
        html:not(.dark) .payments-popover::before {
            border-color: #dbe3ee;
            background: #fff;
        }
        html:not(.dark) .payments-notice { border-color: #e2e8f0; }
        html:not(.dark) .payments-notice-main span { color: #475569; }
        html:not(.dark) .payments-notice-cta {
            border-color: #bfdbfe;
            color: #1e3a8a;
            background: linear-gradient(145deg, #eff6ff, #dbeafe);
            box-shadow: 0 4px 0 rgb(148 163 184 / 30%);
        }
        html:not(.dark) .payments-search,
        html:not(.dark) .payments-status-select {
            border-color: #cbd5e1;
            color: #0f172a;
            background-color: #fff;
            background-image: none;
            box-shadow: inset 0 1px 2px rgb(15 23 42 / 5%);
        }
        html:not(.dark) .payments-search::placeholder { color: #64748b; }
        html:not(.dark) .payments-status-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='m2 4 4 4 4-4' fill='none' stroke='%23475569' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .9rem center;
            background-size: .75rem;
        }
        html:not(.dark) .payments-status-select option { color: #0f172a; background: #fff; }
        html:not(.dark) .payments-table-scroll {
            border-color: #e2e8f0;
            background: rgb(248 250 252 / 80%);
            scrollbar-color: #cbd5e1 #f1f5f9;
        }
        html:not(.dark) .payments-table thead { background: #f1f5f9; }
        html:not(.dark) .payments-table th { color: #475569; }
        html:not(.dark) .payments-table td { border-color: #e2e8f0; }
        html:not(.dark) .payments-table tbody tr:hover { background: #eff6ff; }
        html:not(.dark) .payments-state-waiting { color: #92400e; }
        html:not(.dark) .payments-state-confirmed { color: #047857; }
        html:not(.dark) .payments-state-cancelled { color: #be123c; }
        html:not(.dark) .payments-state-other { color: #475569; }
        html:not(.dark) .payments-action-proof { border-color: #cbd5e1; color: #1e293b; background: #e2e8f0; }
        html:not(.dark) .payments-action-approve { border-color: #a7f3d0; color: #065f46; background: #d1fae5; }
        html:not(.dark) .payments-action-cancel { border-color: #fde68a; color: #92400e; background: #fef3c7; }
        html:not(.dark) .payments-action-delete { border-color: #fecdd3; color: #9f1239; background: #ffe4e6; }
        html:not(.dark) .payments-flash { color: #047857; background: #ecfdf5; }
        html:not(.dark) .payments-flash-warning { color: #92400e; background: #fffbeb; }
        html:not(.dark) .payments-errors { color: #9f1239; background: #fff1f2; }
        html:not(.dark) .payments-dialog {
            border-color: #cbd5e1;
            background: #fff;
            box-shadow: 0 30px 90px rgb(15 23 42 / 25%);
        }
        html:not(.dark) .payments-dialog::backdrop { background: rgb(15 23 42 / 35%); }
        html:not(.dark) .payments-dialog-head { border-color: #e2e8f0; }
        html:not(.dark) .payments-dialog-close { border-color: #cbd5e1; color: #334155; background: #f1f5f9; }
    </style>
</head>
<body class="payments-portal">
    <div class="payments-glow payments-glow-one" aria-hidden="true"></div>
    <div class="payments-glow payments-glow-two" aria-hidden="true"></div>
    <div class="payments-shell">
        <header class="payments-topbar">
            <a class="payments-brand" href="{{ route('admin.dashboard') }}" aria-label="{{ __('Hotel HAR Official, dashboard admin') }}">
                <img src="{{ asset('assets/logo.png') }}" alt="">
                <span>Hotel HAR Official</span>
            </a>

            <div class="payments-account">
                <div class="payments-bell-wrap">
                    <button
                        class="payments-bell"
                        type="button"
                        aria-label="{{ __('Buka notifikasi pesanan baru') }}"
                        aria-controls="payments-notifications"
                        aria-expanded="false"
                        data-notification-toggle
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                        @if ($pendingCount > 0)
                            <span class="payments-badge">{{ $pendingCount > 99 ? '99+' : $pendingCount }}</span>
                        @endif
                    </button>
                    <section class="payments-popover" id="payments-notifications" data-notification-popover hidden aria-label="{{ __('Pesanan baru') }}">
                        <div class="payments-popover-head">
                            <strong>{{ __('Pesanan baru') }}</strong>
                            <span>{{ __(':count menunggu verifikasi', ['count' => $pendingCount]) }}</span>
                        </div>
                        @forelse ($recentPending as $notice)
                            <article class="payments-notice">
                                <div class="payments-notice-main">
                                    <strong>#{{ $notice->id }} - {{ $notice->guest_name }}</strong>
                                    <span>{{ $notice->room->name }}{{ $notice->room_numbers ? ' - '.implode(', ', $notice->room_numbers) : '' }} - Rp{{ number_format((float) $notice->total_price, 0, ',', '.') }}</span>
                                </div>
                                <time>{{ $notice->payment_submitted_at?->locale(app()->getLocale())->diffForHumans() ?? __('baru saja') }}</time>
                            </article>
                        @empty
                            <p class="payments-notice-empty">{{ __('Belum ada pesanan yang menunggu verifikasi.') }}</p>
                        @endforelse
                        <a class="payments-notice-cta" href="{{ route('admin.payment-reviews.index', ['status' => 'waiting']) }}">{{ __('Menuju halaman verifikasi') }}</a>
                    </section>
                </div>

                <span class="payments-admin-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <div class="payments-admin-name">
                    <strong>{{ auth()->user()->name }}</strong>
                    <span>{{ __(ucfirst(auth()->user()->role)) }}</span>
                </div>
                <form class="payments-logout" action="{{ route('logout') }}" method="post">
                    @csrf
                    <button type="submit">{{ __('Keluar') }}</button>
                </form>
                <x-preferences-controls variant="desktop" />
            </div>
        </header>

        <main>
            <section class="payments-intro">
                <p class="payments-eyebrow">{{ __('Portal pemilik hotel') }}</p>
                <h1 class="payments-title">{{ __('Verifikasi Pembayaran') }}</h1>
                <p class="payments-subtitle">{{ __('Buka bukti transfer dan cocokkan nominal serta rekening tujuan sebelum menyetujui pembayaran.') }}</p>
            </section>

            @if (session('status'))
                <div class="payments-flash" role="status">{{ __(session('status')) }}</div>
            @endif
            @if (session('warning'))
                <div class="payments-flash payments-flash-warning" role="alert">{{ __(session('warning')) }}</div>
            @endif
            @if ($errors->any())
                <div class="payments-errors" role="alert">
                    <strong>{{ __('Periksa kembali tindakan yang Anda pilih.') }}</strong>
                    <ul>@foreach ($errors->all() as $error)<li>{{ __($error) }}</li>@endforeach</ul>
                </div>
            @endif

            <section class="payments-filter-panel" aria-label="{{ __('Pencarian dan filter pesanan') }}">
                <form class="payments-filter-form" action="{{ route('admin.payment-reviews.index') }}" method="get">
                    <div class="payments-field payments-search-field">
                        <label for="booking-search">{{ __('Cari pesanan') }}</label>
                        <div class="payments-search-wrap">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
                            <input
                                class="payments-search"
                                id="booking-search"
                                name="q"
                                type="search"
                                value="{{ $search }}"
                                placeholder="{{ __('Cari nama tamu, #pesanan, kamar, HP, atau email') }}"
                                autocomplete="off"
                            >
                        </div>
                    </div>
                    <div class="payments-field">
                        <label for="booking-status">{{ __('Status pesanan') }}</label>
                        <select class="payments-status-select" id="booking-status" name="status">
                            <option value="all" @selected($status === 'all')>{{ __('Semua Status') }}</option>
                            <option value="waiting" @selected($status === 'waiting')>{{ __('Menunggu Verifikasi') }}</option>
                            <option value="confirmed" @selected($status === 'confirmed')>{{ __('Terkonfirmasi') }}</option>
                            <option value="cancelled" @selected($status === 'cancelled')>{{ __('Dibatalkan') }}</option>
                        </select>
                    </div>
                    <button class="payments-filter-submit" type="submit">{{ __('Terapkan') }}</button>
                </form>
            </section>

            <section class="payments-table-card" aria-label="{{ __('Data pesanan penginapan') }}">
                <div class="payments-table-scroll">
                    <table class="payments-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('No. Pesanan & Tanggal') }}</th>
                                <th scope="col">{{ __('Nama Tamu & Email') }}</th>
                                <th scope="col">{{ __('Tipe Kamar &amp; No.') }}</th>
                                <th scope="col">Check-in / Check-out</th>
                                <th scope="col">{{ __('Total Tagihan') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col" style="text-align:right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reservations as $reservation)
                                @php
                                    $isWaiting = $reservation->payment_status === 'waiting_confirmation';
                                    $isConfirmed = $reservation->status === 'confirmed' || $reservation->payment_status === 'paid';
                                    $isCancelled = in_array($reservation->status, ['cancelled', 'expired', 'refunded'], true)
                                        || $reservation->payment_status === 'cancelled';
                                    $statusLabel = $isWaiting
                                        ? __('Menunggu Verifikasi')
                                        : ($isConfirmed
                                            ? __('Terkonfirmasi')
                                            : ($isCancelled ? __('Dibatalkan') : __('Menunggu Pembayaran')));
                                    $statusClass = $isWaiting
                                        ? 'payments-state-waiting'
                                        : ($isConfirmed
                                            ? 'payments-state-confirmed'
                                            : ($isCancelled ? 'payments-state-cancelled' : 'payments-state-other'));
                                @endphp
                                <tr>
                                    <td>
                                        <div class="payments-primary-cell">
                                            <strong>#{{ $reservation->id }}</strong>
                                            <span>{{ $reservation->created_at->locale(app()->getLocale())->translatedFormat('d M Y') }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="payments-guest">
                                            <strong>{{ $reservation->guest_name }}</strong>
                                            <span>{{ $reservation->guest_email }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="payments-room">
                                            {{ $reservation->room->name }}
                                            <span>{{ $reservation->room_numbers ? __('No. ').implode(', ', $reservation->room_numbers) : $reservation->hotel->name }}</span>
                                        </div>
                                    </td>
                                    <td class="payments-date">
                                        {{ $reservation->check_in->locale(app()->getLocale())->translatedFormat('d M Y') }} <span>{{ substr($reservation->check_in_time ?? '14:00', 0, 5) }}</span>
                                        — {{ $reservation->check_out->locale(app()->getLocale())->translatedFormat('d M Y') }} <span>{{ substr($reservation->check_out_time ?? '12:00', 0, 5) }}</span>
                                    </td>
                                    <td><span class="payments-price">Rp{{ number_format((float) $reservation->total_price, 0, ',', '.') }}</span></td>
                                    <td><span class="payments-state {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                    <td>
                                        <div class="payments-actions">
                                            <button
                                                class="payments-action"
                                                type="button"
                                                data-booking-detail
                                                data-order="#{{ $reservation->id }}"
                                                data-guest="{{ $reservation->guest_name }}"
                                                data-email="{{ $reservation->guest_email }}"
                                                data-phone="{{ $reservation->guest_phone ?: __('Tidak tersedia') }}"
                                                data-identity-type="{{ $reservation->identity_type === 'ktp' ? 'KTP' : ($reservation->identity_type === 'passport' ? __('Paspor') : __('Tidak tersedia')) }}"
                                                data-identity-number="{{ $reservation->identity_number ?: __('Tidak tersedia') }}"
                                                data-special-request="{{ $reservation->special_request ?: __('Tidak ada permintaan khusus') }}"
                                                data-room="{{ $reservation->room->name }}{{ $reservation->room_numbers ? ' - '.implode(', ', $reservation->room_numbers) : '' }}"
                                                data-hotel="{{ $reservation->hotel->name }}"
                                                data-dates="{{ $reservation->check_in->locale(app()->getLocale())->translatedFormat('d M Y') }} {{ substr($reservation->check_in_time ?? '14:00', 0, 5) }} — {{ $reservation->check_out->locale(app()->getLocale())->translatedFormat('d M Y') }} {{ substr($reservation->check_out_time ?? '12:00', 0, 5) }}"
                                                data-total="Rp{{ number_format((float) $reservation->total_price, 0, ',', '.') }}"
                                                data-status="{{ $statusLabel }}"
                                                data-payment="{{ config('payments.methods.'.$reservation->payment_method.'.label', $reservation->destination_bank ?: __('Belum tersedia')) }}"
                                                data-bank="{{ trim(($reservation->account_name ?: __('Nama tidak tersedia')).' · '.($reservation->bank_name ?: __('Bank tidak disebutkan'))) }}"
                                                data-submitted="{{ $reservation->payment_submitted_at ? $reservation->payment_submitted_at->locale(app()->getLocale())->translatedFormat('d M Y, H:i').' WIB' : __('Belum mengirim bukti') }}"
                                            >{{ __('Detail') }}</button>
                                            @if ($reservation->payment_proof)
                                                <a class="payments-action payments-action-proof" href="{{ route('admin.payment-reviews.proof', $reservation) }}" target="_blank" rel="noopener">{{ __('Bukti Transfer') }}</a>
                                            @else
                                                <button class="payments-action payments-action-proof" type="button" disabled title="{{ __('Bukti pembayaran belum tersedia') }}">{{ __('Bukti Transfer') }}</button>
                                            @endif
                                            @if ($isWaiting)
                                                <form class="payments-action-form" action="{{ route('admin.payment-reviews.review', $reservation) }}" method="post">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="decision" value="approve">
                                                    <button class="payments-action payments-action-approve" type="submit">{{ __('Setujui') }}</button>
                                                </form>
                                                <form class="payments-action-form" action="{{ route('admin.payment-reviews.review', $reservation) }}" method="post">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="decision" value="cancel">
                                                    <button class="payments-action payments-action-cancel" type="submit">{{ __('Batal') }}</button>
                                                </form>
                                            @endif
                                            <form class="payments-action-form" action="{{ route('admin.payment-reviews.destroy', $reservation) }}" method="post" data-delete-booking>
                                                @csrf
                                                @method('DELETE')
                                                <button class="payments-action payments-action-delete" type="submit">{{ __('Hapus') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="payments-empty">
                                            <strong>{{ __('Tidak ada pesanan yang cocok') }}</strong>
                                            <span>{{ __('Coba ubah kata kunci atau filter status yang dipilih.') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <footer class="payments-table-footer">
                    <span class="payments-table-count">
                        {{ __('Menampilkan :from-:to dari :total pesanan', ['from' => $reservations->firstItem() ?? 0, 'to' => $reservations->lastItem() ?? 0, 'total' => $reservations->total()]) }}
                    </span>
                    <nav class="payments-pagination" aria-label="{{ __('Navigasi halaman pesanan') }}">
                        @if ($reservations->previousPageUrl())
                            <a class="payments-page-link" href="{{ $reservations->previousPageUrl() }}">{{ __('Sebelumnya') }}</a>
                        @else
                            <span class="payments-page-link" aria-disabled="true">{{ __('Sebelumnya') }}</span>
                        @endif
                        <span class="payments-page-number">{{ __('Hal. :current / :last', ['current' => $reservations->currentPage(), 'last' => $reservations->lastPage()]) }}</span>
                        @if ($reservations->nextPageUrl())
                            <a class="payments-page-link" href="{{ $reservations->nextPageUrl() }}">{{ __('Berikutnya') }}</a>
                        @else
                            <span class="payments-page-link" aria-disabled="true">{{ __('Berikutnya') }}</span>
                        @endif
                    </nav>
                </footer>
            </section>
        </main>
    </div>

    <dialog class="payments-dialog" data-booking-dialog aria-labelledby="booking-dialog-title">
        <div class="payments-dialog-head">
            <div>
                <p>{{ __('Informasi reservasi') }}</p>
                <h2 id="booking-dialog-title" data-dialog-order>{{ __('Detail pesanan') }}</h2>
            </div>
            <button class="payments-dialog-close" type="button" data-dialog-close aria-label="{{ __('Tutup detail') }}">×</button>
        </div>
        <div class="payments-dialog-body">
            <div class="payments-dialog-item"><span>{{ __('Nama tamu') }}</span><strong data-dialog-guest></strong></div>
            <div class="payments-dialog-item"><span>Email</span><strong data-dialog-email></strong></div>
            <div class="payments-dialog-item"><span>{{ __('Nomor HP') }}</span><strong data-dialog-phone></strong></div>
            <div class="payments-dialog-item"><span>{{ __('Tipe identitas') }}</span><strong data-dialog-identity-type></strong></div>
            <div class="payments-dialog-item"><span>{{ __('Nomor identitas') }}</span><strong data-dialog-identity-number></strong></div>
            <div class="payments-dialog-item payments-dialog-item-wide"><span>{{ __('Permintaan khusus') }} ({{ __('opsional') }})</span><strong data-dialog-special-request></strong></div>
            <div class="payments-dialog-item"><span>{{ __('Kamar') }}</span><strong data-dialog-room></strong></div>
            <div class="payments-dialog-item"><span>Hotel</span><strong data-dialog-hotel></strong></div>
            <div class="payments-dialog-item"><span>{{ __('Check-in / Check-out') }}</span><strong data-dialog-dates></strong></div>
            <div class="payments-dialog-item"><span>{{ __('Total tagihan') }}</span><strong data-dialog-total></strong></div>
            <div class="payments-dialog-item"><span>{{ __('Status pesanan') }}</span><strong data-dialog-status></strong></div>
            <div class="payments-dialog-item"><span>{{ __('Metode pembayaran') }}</span><strong data-dialog-payment></strong></div>
            <div class="payments-dialog-item"><span>{{ __('Rekening pengirim') }}</span><strong data-dialog-bank></strong></div>
            <div class="payments-dialog-item"><span>{{ __('Bukti dikirim') }}</span><strong data-dialog-submitted></strong></div>
        </div>
        <div class="payments-dialog-foot">
            <button class="payments-action" type="button" data-dialog-close>{{ __('Tutup') }}</button>
        </div>
    </dialog>

    <script>
        (() => {
            const toggle = document.querySelector('[data-notification-toggle]');
            const popover = document.querySelector('[data-notification-popover]');
            const dialog = document.querySelector('[data-booking-dialog]');

            const alignMobilePopover = () => {
                if (!toggle || !popover) return;
                if (!window.matchMedia('(max-width: 640px)').matches) {
                    popover.style.removeProperty('right');
                    return;
                }

                const topbar = document.querySelector('.payments-topbar');
                if (!topbar) return;

                const viewportRight = document.documentElement.clientWidth - parseFloat(getComputedStyle(document.body).paddingRight);
                popover.style.right = `${Math.max(0, viewportRight - topbar.getBoundingClientRect().right)}px`;
            };

            const setPopover = (open) => {
                if (!toggle || !popover) return;
                if (open) alignMobilePopover();
                popover.hidden = !open;
                toggle.setAttribute('aria-expanded', String(open));
            };

            window.addEventListener('resize', () => {
                if (popover && !popover.hidden) alignMobilePopover();
            });

            toggle?.addEventListener('click', (event) => {
                event.stopPropagation();
                setPopover(popover.hidden);
            });

            document.addEventListener('click', (event) => {
                if (popover && !popover.hidden && !popover.contains(event.target) && !toggle?.contains(event.target)) {
                    setPopover(false);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') setPopover(false);
            });

            document.querySelectorAll('[data-booking-detail]').forEach((button) => {
                button.addEventListener('click', () => {
                    const fields = ['order', 'guest', 'email', 'phone', 'identity-type', 'identity-number', 'special-request', 'room', 'hotel', 'dates', 'total', 'status', 'payment', 'bank', 'submitted'];
                    fields.forEach((field) => {
                        const target = dialog?.querySelector(`[data-dialog-${field}]`);
                        if (target) target.textContent = button.getAttribute(`data-${field}`) || '—';
                    });
                    if (typeof dialog?.showModal === 'function') dialog.showModal();
                    else dialog?.setAttribute('open', '');
                });
            });

            document.querySelectorAll('[data-dialog-close]').forEach((button) => {
                button.addEventListener('click', () => dialog?.close());
            });

            dialog?.addEventListener('click', (event) => {
                if (event.target === dialog) dialog.close();
            });

            document.querySelectorAll('[data-delete-booking]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    if (!window.confirm(@json(__('Hapus pesanan ini secara permanen?')))) event.preventDefault();
                });
            });
        })();
    </script>
    @include('partials.help')
</body>
</html>
