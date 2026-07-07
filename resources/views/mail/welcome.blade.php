@php($logoUrl = public_url('images/mail/sourcenest-logo.png'))
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>SourceNest — B1 Welcome</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500;1,600&display=swap"
        rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* ═══════════════════════════════════
            TOKENS — SourceNest Brand System
        ═══════════════════════════════════ */
        :root {
            --br: #3B2800;
            --br-mid: #5C3D10;
            --br-tan: #9A7A3A;
            --br-gold: #C8A96A;
            --br-pale: #E8D5A8;
            --br-tint: #FBF7EE;
            --br-soft: #F2E8D0;

            --n900: #0A0A0A;
            --n800: #1C1C1C;
            --n700: #2E2E2E;
            --n600: #464646;
            --n500: #666;
            --n400: #8A8A8A;
            --n300: #B4B4B4;
            --n200: #D6D6D6;
            --n150: #E6E6E6;
            --n100: #F0F0F0;
            --n50: #F8F8F8;
            --wh: #FFFFFF;

            --g-dk: #0A5C32;
            --g-md: #0E8A4A;
            --g-lt: #12B060;
            --g-bg: #EAFAF2;
            --g-bd: #6ECFA0;

            --a-dk: #7A4D00;
            --a-md: #C07800;
            --a-lt: #F0A000;
            --a-bg: #FFF8E4;
            --a-bd: #F0C040;

            --r-dk: #7A1818;
            --r-md: #C42828;
            --r-lt: #E83838;
            --r-bg: #FEF2F2;
            --r-bd: #EEAAAA;

            --b-dk: #0C3C70;
            --b-md: #1464C8;
            --b-bg: #EEF3FF;
            --b-bd: #90B4F0;

            --buyer: #1258B8;
            --buyer-bg: #EDF2FF;
            --buyer-bd: #A8C0F0;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: #C8C2B6;
            min-height: 100vh;
        }

        /* ═══════════ NAV ═══════════ */
        .page {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .nav {
            background: var(--br);
            display: flex;
            align-items: stretch;
            padding-right: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, .06);
            position: sticky;
            top: 0;
            z-index: 800;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .nav::-webkit-scrollbar {
            display: none;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 0 18px;
            flex-shrink: 0;
            border-right: 1px solid rgba(255, 255, 255, .07);
        }

        .nav-wm {
            font: 900 14px/1 'Nunito', sans-serif;
            color: #fff;
            letter-spacing: -.4px;
        }

        .nav-sep {
            width: 1px;
            background: rgba(255, 255, 255, .07);
            margin: 13px 0;
            flex-shrink: 0;
        }

        .nav-grp {
            display: flex;
            align-items: stretch;
        }

        .nav-cap {
            padding: 0 10px;
            display: flex;
            align-items: center;
            font: 900 7px/1 'Nunito', sans-serif;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: rgba(200, 169, 106, .32);
            flex-shrink: 0;
            white-space: nowrap;
        }

        .nt {
            padding: 0 12px;
            height: 50px;
            font: 700 9px/1 'Nunito', sans-serif;
            letter-spacing: .3px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .26);
            background: none;
            border: none;
            border-bottom: 2.5px solid transparent;
            cursor: pointer;
            transition: all .13s;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .nt:hover {
            color: rgba(255, 255, 255, .58);
        }

        .nt.on {
            color: #fff;
            border-bottom-color: var(--br-gold);
        }

        /* ═══════════ CANVAS ═══════════ */
        .canvas {
            padding: 44px 20px 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* ═══════════ EMAIL SHELL ═══════════ */
        .email {
            display: none;
            width: 100%;
            max-width: 600px;
            background: var(--wh);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, .055), 0 4px 16px rgba(0, 0, 0, .06), 0 20px 56px rgba(0, 0, 0, .09), 0 56px 96px rgba(0, 0, 0, .04);
            animation: lift .25s cubic-bezier(.22, .68, 0, 1.2) both;
        }

        .email.on {
            display: block;
        }

        @keyframes lift {
            from {
                opacity: 0;
                transform: translateY(14px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* subject */
        .sp {
            background: var(--n50);
            border-bottom: 1px solid var(--n150);
            padding: 9px 30px;
            display: flex;
            align-items: baseline;
            gap: 10px;
        }

        .sp-l {
            font: 900 7.5px/1 'Nunito', sans-serif;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--n300);
            flex-shrink: 0;
        }

        .sp-v {
            font: 500 12.5px/1 'Nunito', sans-serif;
            color: var(--n700);
        }

        /* ═══════════ LOGO ═══════════ */
        .logo-lk {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-wm {
            font: 900 21px/1 'Nunito', sans-serif;
            color: var(--br);
            letter-spacing: -.6px;
        }

        .logo-sub {
            font: 700 8px/1 'Nunito', sans-serif;
            letter-spacing: .9px;
            text-transform: uppercase;
            color: var(--br-tan);
            margin-top: 2px;
        }

        /* ═══════════ HEADERS ═══════════ */
        /* A — white standard */
        .hd-a {
            background: var(--wh);
            padding: 20px 30px;
            border-bottom: 1.5px solid var(--n100);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* B — brand dark */
        .hd-b {
            background: var(--br);
            padding: 20px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .hd-b .logo-wm {
            color: #fff;
        }

        .hd-b .logo-sub {
            color: rgba(200, 169, 106, .5);
        }

        /* C — centered tint */
        .hd-c {
            background: var(--br-tint);
            padding: 22px 30px 18px;
            border-bottom: 1.5px solid var(--br-pale);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .hd-ctx {
            font: 800 8.5px/1 'Nunito', sans-serif;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: var(--br-tan);
            margin-top: 10px;
        }

        /* D — split left accent bar */
        .hd-d {
            background: var(--wh);
            padding: 0;
            border-bottom: 1.5px solid var(--n100);
            display: flex;
            align-items: stretch;
        }

        .hd-d-bar {
            width: 4px;
            background: var(--br-tan);
            flex-shrink: 0;
        }

        .hd-d-inner {
            padding: 20px 26px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex: 1;
        }

        .hd-badge {
            font: 700 9px/1 'Nunito', sans-serif;
            letter-spacing: .4px;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 20px;
            color: var(--n400);
            background: var(--n50);
            border: 1.5px solid var(--n150);
        }

        .hd-b .hd-badge {
            color: rgba(200, 169, 106, .5);
            background: transparent;
            border-color: rgba(200, 169, 106, .18);
        }

        /* ═══════════ HEROES ═══════════ */
        /* H1 — tinted, globe watermark, left */
        .h1 {
            background: var(--br-tint);
            padding: 34px 30px 40px;
            position: relative;
            overflow: hidden;
            border-bottom: 1.5px solid var(--br-pale);
        }

        .h1-wm {
            position: absolute;
            right: -24px;
            top: -24px;
            width: 210px;
            height: 210px;
            opacity: .06;
            pointer-events: none;
        }

        /* H2 — white, large ghost numeral */
        .h2 {
            background: var(--wh);
            padding: 34px 30px 32px;
            border-bottom: 2px solid var(--n100);
            position: relative;
            overflow: hidden;
        }

        .h2-deco {
            position: absolute;
            right: 16px;
            top: -10px;
            font: 600 108px/1 'Lora', serif;
            color: var(--br-pale);
            pointer-events: none;
            opacity: .5;
        }

        /* H3 — compact gray */
        .h3 {
            background: var(--n50);
            padding: 26px 30px 24px;
            border-bottom: 1.5px solid var(--n150);
        }

        /* H4 — dark, grid texture, centered (security) */
        .h4 {
            background: var(--br);
            padding: 40px 30px 42px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .h4-grid {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(200, 169, 106, .05) 1px, transparent 1px), linear-gradient(90deg, rgba(200, 169, 106, .05) 1px, transparent 1px);
            background-size: 26px 26px;
            pointer-events: none;
        }

        .h4-fade {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(transparent, rgba(59, 40, 0, .35));
            pointer-events: none;
        }

        /* H5 — notification horizontal */
        .h5 {
            background: linear-gradient(135deg, var(--br-tint) 0%, var(--wh) 55%);
            padding: 26px 30px;
            border-bottom: 1.5px solid var(--br-pale);
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .h5-ico {
            width: 58px;
            height: 58px;
            flex-shrink: 0;
            background: var(--wh);
            border: 1.5px solid var(--br-pale);
            border-radius: 14px;
            display: grid;
            place-items: center;
            box-shadow: 0 2px 10px rgba(59, 40, 0, .06), 0 0 0 5px var(--br-tint);
        }

        /* H6 — admin compact (gray horizontal) */
        .h6 {
            background: var(--n50);
            padding: 22px 30px;
            border-bottom: 1.5px solid var(--n150);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .h6-ico {
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            background: var(--wh);
            border: 1.5px solid var(--n200);
            border-radius: 10px;
            display: grid;
            place-items: center;
        }

        /* hero typography */
        .pill-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
        }

        .mpill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 11px;
            border-radius: 20px;
            border: 1.5px solid;
        }

        .pill-buyer {
            background: var(--buyer-bg);
            border-color: var(--buyer-bd);
        }

        .pill-mfr {
            background: var(--br-tint);
            border-color: var(--br-pale);
        }

        .pill-sec {
            background: var(--n50);
            border-color: var(--n200);
        }

        .pill-admin {
            background: #FFF5E6;
            border-color: #F0C080;
        }

        .pill-ok {
            background: var(--g-bg);
            border-color: var(--g-bd);
        }

        .pill-warn {
            background: var(--a-bg);
            border-color: var(--a-bd);
        }

        .pdot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .pd-buyer {
            background: var(--buyer);
        }

        .pd-mfr {
            background: var(--br-tan);
        }

        .pd-sec {
            background: var(--n400);
        }

        .pd-admin {
            background: #D07800;
        }

        .pd-ok {
            background: var(--g-md);
        }

        .pd-warn {
            background: var(--a-md);
        }

        .pill-txt {
            font: 800 8.5px/1 'Nunito', sans-serif;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--n500);
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 13px;
        }

        .ew-ln {
            width: 20px;
            height: 2px;
            border-radius: 1px;
            background: var(--br-pale);
            flex-shrink: 0;
        }

        .ew-tx {
            font: 800 8.5px/1 'Nunito', sans-serif;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--br-tan);
        }

        .h4 .ew-ln {
            background: rgba(200, 169, 106, .4);
        }

        .h4 .ew-tx {
            color: rgba(200, 169, 106, .7);
        }

        .h6 .ew-ln {
            background: var(--n200);
        }

        .h6 .ew-tx {
            color: var(--n400);
        }

        .ht {
            font: 500 31px/1.17 'Lora', serif;
            color: var(--br);
            letter-spacing: -.2px;
        }

        .ht em {
            font-style: italic;
            color: var(--br-tan);
        }

        .h3 .ht {
            font-size: 25px;
            color: var(--n800);
        }

        .h3 .ht em {
            color: var(--br-tan);
        }

        .h4 .ht {
            font-size: 30px;
            color: #fff;
        }

        .h4 .ht em {
            color: var(--br-gold);
        }

        .h5 .ht {
            font-size: 22px;
        }

        .h5 .ht em {
            color: var(--br-tan);
        }

        .h2 .ht {
            font-size: 27px;
        }

        .h6 .ht {
            font-size: 20px;
            color: var(--n800);
        }

        .h6 .ht em {
            color: var(--br-tan);
        }

        .hs {
            margin-top: 12px;
            font: 400 13.5px/1.78 'Nunito', sans-serif;
            color: var(--n500);
        }

        .h3 .hs {
            color: var(--n400);
            font-size: 13px;
        }

        .h4 .hs {
            color: rgba(255, 255, 255, .4);
            font-size: 13px;
            max-width: 340px;
            margin: 10px auto 0;
        }

        .h5 .hs {
            font-size: 13px;
            margin-top: 6px;
        }

        .h6 .hs {
            font-size: 13px;
            color: var(--n400);
            margin-top: 4px;
        }

        /* ═══════════════════════════════════════
        FLOW PROGRESS TRACKER (v7 — redesigned)
        Clean horizontal stepper with connectors
                ═══════════════════════════════════════ */
        .flow-tracker {
            margin-top: 20px;
            padding: 20px 18px;
            background: var(--n50);
            border: 1.5px solid var(--n150);
            border-radius: 12px;
        }

        .ft-steps {
            display: flex;
            align-items: flex-start;
            position: relative;
        }

        .ft-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
            z-index: 1;
        }

        /* connector line between steps */
        .ft-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 16px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: var(--n200);
            z-index: 0;
        }

        .ft-step.done:not(:last-child)::after {
            background: var(--g-bd);
        }

        .ft-step.current:not(:last-child)::after {
            background: linear-gradient(to right, var(--a-bd), var(--n200));
        }

        /* circle badge */
        .ft-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            position: relative;
            z-index: 2;
            flex-shrink: 0;
            transition: all .2s;
        }

        .ft-step.done .ft-circle {
            background: var(--g-bg);
            border: 2px solid var(--g-bd);
        }

        .ft-step.current .ft-circle {
            background: var(--a-bg);
            border: 2px solid var(--a-md);
            box-shadow: 0 0 0 4px rgba(192, 120, 0, .1);
        }

        .ft-step.upcoming .ft-circle {
            background: var(--n100);
            border: 2px solid var(--n200);
        }

        .ft-step.active .ft-circle {
            background: var(--g-bg);
            border: 2px solid var(--g-bd);
            box-shadow: 0 0 0 4px rgba(14, 138, 74, .1);
        }

        /* label + desc */
        .ft-meta {
            margin-top: 8px;
            text-align: center;
            padding: 0 4px;
        }

        .ft-label {
            font: 800 9px/1 'Nunito', sans-serif;
            letter-spacing: .5px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .ft-step.done .ft-label {
            color: var(--g-dk);
        }

        .ft-step.current .ft-label {
            color: var(--a-dk);
        }

        .ft-step.upcoming .ft-label {
            color: var(--n400);
        }

        .ft-step.active .ft-label {
            color: var(--g-dk);
        }

        .ft-desc {
            font: 400 10px/1.4 'Nunito', sans-serif;
            color: var(--n300);
        }

        .ft-step.current .ft-desc {
            color: var(--a-md);
        }

        .ft-step.done .ft-desc,
        .ft-step.active .ft-desc {
            color: var(--g-md);
        }

        /* ═══════════ SECTIONS ═══════════ */
        .sec {
            padding: 28px 30px;
            border-bottom: 1px solid var(--n100);
        }

        .sec:last-of-type {
            border-bottom: none;
        }

        .sec.wh {
            background: var(--wh);
        }

        .sec.gs {
            background: var(--n50);
        }

        .sec.tint {
            background: var(--br-tint);
        }

        .sec.dk {
            background: var(--br);
        }

        .sh {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 18px;
        }

        .sh-bar {
            width: 3px;
            height: 18px;
            border-radius: 2px;
            background: var(--br-pale);
            flex-shrink: 0;
        }

        .sh-txt {
            font: 500 17px/1 'Lora', serif;
            color: var(--br);
        }

        .sh-txt em {
            font-style: italic;
            color: var(--br-tan);
        }

        .greeting {
            font: 500 17px/1 'Lora', serif;
            color: var(--br);
            margin-bottom: 13px;
        }

        .p {
            font: 400 13.5px/1.88 'Nunito', sans-serif;
            color: var(--n600);
            margin-bottom: 13px;
        }

        .p:last-child {
            margin-bottom: 0;
        }

        .p strong {
            font-weight: 700;
            color: var(--n800);
        }

        .p a {
            color: var(--br-tan);
            text-decoration: none;
            border-bottom: 1px solid rgba(154, 122, 58, .3);
        }

        .p.lead {
            font-size: 14.5px;
            font-weight: 600;
            color: var(--n700);
        }

        /* ═══════════ ACCESS STATE BLOCK ═══════════ */
        .access-state {
            border: 1.5px solid;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 18px;
        }

        .access-state.locked {
            border-color: var(--r-bd);
        }

        .access-state.pending {
            border-color: var(--a-bd);
        }

        .access-state.active {
            border-color: var(--g-bd);
        }

        .as-head {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid;
        }

        .access-state.locked .as-head {
            background: var(--r-bg);
            border-color: var(--r-bd);
        }

        .access-state.pending .as-head {
            background: var(--a-bg);
            border-color: var(--a-bd);
        }

        .access-state.active .as-head {
            background: var(--g-bg);
            border-color: var(--g-bd);
        }

        .as-ico {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1.5px solid;
        }

        .access-state.locked .as-ico {
            background: var(--r-bg);
            border-color: var(--r-bd);
        }

        .access-state.pending .as-ico {
            background: var(--a-bg);
            border-color: var(--a-bd);
        }

        .access-state.active .as-ico {
            background: var(--g-bg);
            border-color: var(--g-bd);
        }

        .as-title {
            font: 500 15px/1 'Lora', serif;
            margin-bottom: 3px;
        }

        .access-state.locked .as-title {
            color: var(--r-dk);
        }

        .access-state.pending .as-title {
            color: var(--a-dk);
        }

        .access-state.active .as-title {
            color: var(--g-dk);
        }

        .as-sub {
            font: 500 11.5px/1 'Nunito', sans-serif;
            color: var(--n400);
        }

        .as-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 16px;
            border-top: 1px solid var(--n100);
        }

        .as-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .locked-dot {
            background: var(--r-md);
        }

        .pending-dot {
            background: var(--a-md);
        }

        .active-dot {
            background: var(--g-md);
        }

        .as-txt {
            font: 500 12.5px/1 'Nunito', sans-serif;
            color: var(--n600);
        }

        .as-txt.strike {
            text-decoration: line-through;
            color: var(--n300);
        }

        .as-txt.ok {
            color: var(--g-dk);
        }

        /* ═══════════ STATS / VALUE TRIPTYCH ═══════════ */
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            border: 1.5px solid var(--n150);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 18px;
        }

        .stat {
            padding: 18px 14px;
            background: var(--wh);
            border-right: 1.5px solid var(--n150);
        }

        .stat:last-child {
            border-right: none;
        }

        .stat-ico {
            width: 28px;
            height: 28px;
            background: var(--br-tint);
            border: 1.5px solid var(--br-pale);
            border-radius: 7px;
            display: grid;
            place-items: center;
            margin-bottom: 10px;
        }

        .stat-t {
            font: 700 13px/1.3 'Nunito', sans-serif;
            color: var(--br);
            margin-bottom: 4px;
        }

        .stat-d {
            font: 400 11.5px/1.5 'Nunito', sans-serif;
            color: var(--n400);
        }

        /* ═══════════ FEATURE CARDS ═══════════ */
        .cards3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 18px;
        }

        .cards2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 18px;
        }

        .card {
            background: var(--wh);
            border: 1.5px solid var(--n150);
            border-radius: 10px;
            padding: 17px 15px;
        }

        .sec.gs .card,
        .sec.tint .card {
            background: var(--wh);
        }

        .c-ico {
            width: 30px;
            height: 30px;
            background: var(--br-tint);
            border: 1.5px solid var(--br-pale);
            border-radius: 8px;
            display: grid;
            place-items: center;
            margin-bottom: 10px;
        }

        .c-t {
            font: 800 12.5px/1.3 'Nunito', sans-serif;
            color: var(--br);
            margin-bottom: 3px;
        }

        .c-d {
            font: 400 11.5px/1.55 'Nunito', sans-serif;
            color: var(--n400);
        }

        /* ═══════════ VALUE STRIP ═══════════ */
        .vstrip {
            border: 1.5px solid var(--n150);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 18px;
        }

        .vr {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 15px 17px;
            border-bottom: 1px solid var(--n100);
        }

        .vr:last-child {
            border-bottom: none;
        }

        .vr-ico {
            width: 28px;
            height: 28px;
            flex-shrink: 0;
            background: var(--br-tint);
            border: 1.5px solid var(--br-pale);
            border-radius: 7px;
            display: grid;
            place-items: center;
            margin-top: 1px;
        }

        .vr-t {
            font: 800 13px/1 'Nunito', sans-serif;
            color: var(--n800);
            margin-bottom: 4px;
        }

        .vr-d {
            font: 400 12px/1.55 'Nunito', sans-serif;
            color: var(--n400);
        }

        /* ═══════════ HOW IT WORKS ═══════════ */
        .howgrid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 18px;
        }

        .hc {
            background: var(--wh);
            border: 1.5px solid var(--n150);
            border-radius: 10px;
            padding: 18px 15px;
            position: relative;
            overflow: hidden;
        }

        .sec.gs .hc {
            background: var(--wh);
        }

        .hc-n {
            position: absolute;
            right: 10px;
            top: -8px;
            font: 600 52px/1 'Lora', serif;
            color: var(--br-pale);
            pointer-events: none;
            opacity: .55;
        }

        .hc-t {
            font: 800 13px/1 'Nunito', sans-serif;
            color: var(--br);
            margin-bottom: 4px;
        }

        .hc-d {
            font: 400 12px/1.55 'Nunito', sans-serif;
            color: var(--n400);
        }

        /* ═══════════ EXPLAINER DARK ═══════════ */
        .explainer {
            background: var(--br);
            border-radius: 10px;
            padding: 22px 18px;
            margin-top: 18px;
        }

        .exp-h {
            font: 500 16px/1 'Lora', serif;
            color: #fff;
            margin-bottom: 14px;
        }

        .exp-h em {
            font-style: italic;
            color: var(--br-gold);
        }

        .exp-row {
            display: flex;
            gap: 11px;
            align-items: flex-start;
            margin-bottom: 11px;
        }

        .exp-row:last-child {
            margin-bottom: 0;
        }

        .exp-n {
            font: 600 11.5px/1.7 'Lora', serif;
            color: var(--br-gold);
            flex-shrink: 0;
            width: 16px;
        }

        .exp-t {
            font: 400 12.5px/1.65 'Nunito', sans-serif;
            color: rgba(255, 255, 255, .55);
        }

        .exp-t strong {
            font-weight: 700;
            color: rgba(255, 255, 255, .88);
        }

        /* ═══════════ STEP LIST ═══════════ */
        .steps {
            margin-top: 18px;
        }

        .step {
            display: flex;
            gap: 14px;
            padding: 15px 0;
            border-bottom: 1px solid var(--n100);
        }

        .step:last-child {
            border-bottom: none;
        }

        .step-nc {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .step-n {
            width: 26px;
            height: 26px;
            background: var(--br-tint);
            border: 1.5px solid var(--br-pale);
            border-radius: 50%;
            display: grid;
            place-items: center;
            font: 900 11px/1 'Nunito', sans-serif;
            color: var(--br-tan);
            flex-shrink: 0;
        }

        .step-conn {
            flex: 1;
            width: 1px;
            background: var(--n150);
            margin-top: 4px;
        }

        .step:last-child .step-conn {
            display: none;
        }

        .step-t {
            font: 700 13.5px/1 'Nunito', sans-serif;
            color: var(--n800);
            margin-bottom: 3px;
        }

        .step-d {
            font: 400 12.5px/1.55 'Nunito', sans-serif;
            color: var(--n400);
        }

        /* ═══════════ TIMELINE ═══════════ */
        .timeline {
            margin-top: 18px;
        }

        .tl {
            display: grid;
            grid-template-columns: 68px 1fr;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid var(--n100);
            align-items: start;
        }

        .tl:last-child {
            border-bottom: none;
        }

        .tl-tag {
            font: 800 9px/1 'Nunito', sans-serif;
            letter-spacing: .7px;
            text-transform: uppercase;
            color: var(--br-tan);
            padding-top: 2px;
        }

        .tl-t {
            font: 700 13px/1 'Nunito', sans-serif;
            color: var(--n800);
            margin-bottom: 3px;
        }

        .tl-d {
            font: 400 12px/1.55 'Nunito', sans-serif;
            color: var(--n400);
        }

        /* ═══════════ STATUS CHIP ═══════════ */
        .status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            border-radius: 8px;
            margin-top: 16px;
            border: 1.5px solid;
        }

        .status.amber {
            background: var(--a-bg);
            border-color: var(--a-bd);
        }

        .status.green {
            background: var(--g-bg);
            border-color: var(--g-bd);
        }

        .status.red {
            background: var(--r-bg);
            border-color: var(--r-bd);
        }

        .status.blue {
            background: var(--b-bg);
            border-color: var(--b-bd);
        }

        .status.gray {
            background: var(--n50);
            border-color: var(--n200);
        }

        .st-l {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .st-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .d-a {
            background: var(--a-md);
        }

        .d-g {
            background: var(--g-md);
        }

        .d-r {
            background: var(--r-md);
        }

        .d-b {
            background: var(--b-md);
        }

        .d-gr {
            background: var(--n400);
        }

        .st-nm {
            font: 700 12.5px/1 'Nunito', sans-serif;
            color: var(--n800);
        }

        .st-dt {
            font: 500 11px/1 'Nunito', sans-serif;
            color: var(--n400);
        }

        /* ═══════════ ALERT BOXES ═══════════ */
        .alert {
            padding: 14px 16px;
            margin-top: 16px;
            border-radius: 8px;
            border: 1.5px solid;
            border-left: 4px solid;
        }

        .alert.brand {
            background: var(--br-tint);
            border-color: var(--br-pale);
            border-left-color: var(--br-tan);
        }

        .alert.ok {
            background: var(--g-bg);
            border-color: var(--g-bd);
            border-left-color: var(--g-dk);
        }

        .alert.warn {
            background: var(--a-bg);
            border-color: var(--a-bd);
            border-left-color: var(--a-dk);
        }

        .alert.err {
            background: var(--r-bg);
            border-color: var(--r-bd);
            border-left-color: var(--r-dk);
        }

        .alert.info {
            background: var(--b-bg);
            border-color: var(--b-bd);
            border-left-color: var(--b-dk);
        }

        .al {
            font: 900 8.5px/1 'Nunito', sans-serif;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .alert.brand .al {
            color: var(--br-tan);
        }

        .alert.ok .al {
            color: var(--g-dk);
        }

        .alert.warn .al {
            color: var(--a-dk);
        }

        .alert.err .al {
            color: var(--r-dk);
        }

        .alert.info .al {
            color: var(--b-dk);
        }

        .ab {
            font: 400 13px/1.65 'Nunito', sans-serif;
            color: var(--n600);
        }

        .ab strong {
            font-weight: 700;
            color: var(--n800);
        }

        /* ═══════════ BANNER ═══════════ */
        .banner {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            padding: 15px 16px;
            margin-top: 16px;
            border-radius: 10px;
            border: 1.5px solid;
        }

        .banner.ok {
            background: var(--g-bg);
            border-color: var(--g-bd);
        }

        .banner.err {
            background: var(--r-bg);
            border-color: var(--r-bd);
        }

        .banner.brand {
            background: var(--br-tint);
            border-color: var(--br-pale);
        }

        .banner.warn {
            background: var(--a-bg);
            border-color: var(--a-bd);
        }

        .bn-ico {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1.5px solid;
        }

        .banner.ok .bn-ico {
            background: var(--wh);
            border-color: var(--g-bd);
        }

        .banner.err .bn-ico {
            background: var(--wh);
            border-color: var(--r-bd);
        }

        .banner.brand .bn-ico {
            background: var(--br-pale);
            border-color: var(--br-gold);
        }

        .banner.warn .bn-ico {
            background: var(--wh);
            border-color: var(--a-bd);
        }

        .bn-t {
            font: 500 15px/1 'Lora', serif;
            color: var(--br);
            margin-bottom: 3px;
        }

        .banner.err .bn-t {
            color: var(--r-dk);
        }

        .bn-s {
            font: 400 12px/1.55 'Nunito', sans-serif;
            color: var(--n400);
        }

        /* ═══════════ PRICE CARD ═══════════ */
        .price-card {
            background: var(--br);
            border-radius: 10px;
            padding: 22px 20px;
            margin-top: 18px;
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .pr-l {}

        .pr-ey {
            font: 900 8px/1 'Nunito', sans-serif;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--br-gold);
            margin-bottom: 6px;
        }

        .pr-amt {
            font: 600 44px/1 'Lora', serif;
            color: #fff;
        }

        .pr-period {
            font: 500 11.5px/1 'Nunito', sans-serif;
            color: rgba(255, 255, 255, .38);
            margin-top: 3px;
        }

        .pr-r {
            flex: 1;
        }

        .pr-inc {
            font: 900 8px/1 'Nunito', sans-serif;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: var(--br-gold);
            margin-bottom: 9px;
        }

        .pr-item {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            margin-bottom: 7px;
            font: 400 12px/1.5 'Nunito', sans-serif;
            color: rgba(255, 255, 255, .55);
        }

        .pr-item::before {
            content: '–';
            color: var(--br-gold);
            flex-shrink: 0;
            font-size: 10px;
            margin-top: 1px;
        }

        /* ═══════════ WHY GRID ═══════════ */
        .why {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border: 1.5px solid var(--n150);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 18px;
        }

        .wc {
            padding: 15px 14px;
            background: var(--wh);
            border-right: 1.5px solid var(--n150);
            border-bottom: 1.5px solid var(--n150);
        }

        .wc:nth-child(2n) {
            border-right: none;
        }

        .wc:nth-last-child(-n+2) {
            border-bottom: none;
        }

        .wc-ico {
            margin-bottom: 8px;
        }

        .wc-t {
            font: 800 12.5px/1 'Nunito', sans-serif;
            color: var(--br);
            margin-bottom: 3px;
        }

        .wc-d {
            font: 400 11.5px/1.5 'Nunito', sans-serif;
            color: var(--n400);
        }

        /* ═══════════ CHECKLIST ═══════════ */
        .chklist {
            border: 1.5px solid var(--n150);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 18px;
        }

        .chi {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 13px 15px;
            border-bottom: 1px solid var(--n100);
        }

        .chi:last-child {
            border-bottom: none;
        }

        .chi-m {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .chi-m.todo {
            background: var(--a-bg);
            border: 1.5px dashed var(--a-bd);
        }

        .chi-m.done {
            background: var(--g-bg);
            border: 1.5px solid var(--g-bd);
        }

        .chi-t {
            font: 700 13px/1 'Nunito', sans-serif;
            color: var(--n700);
        }

        .chi-d {
            font: 400 12px/1.45 'Nunito', sans-serif;
            color: var(--n400);
            margin-top: 3px;
        }

        /* ═══════════ INQUIRY CARD ═══════════ */
        .inq {
            border: 1.5px solid var(--n150);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 18px;
        }

        .inq-hd {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 17px;
            background: var(--n50);
            border-bottom: 1px solid var(--n150);
        }

        .inq-av {
            width: 38px;
            height: 38px;
            background: var(--br);
            border-radius: 8px;
            display: grid;
            place-items: center;
            font: 600 14px/1 'Lora', serif;
            color: var(--br-gold);
            flex-shrink: 0;
        }

        .inq-nm {
            font: 800 13.5px/1 'Nunito', sans-serif;
            color: var(--br);
        }

        .inq-co {
            font: 500 11.5px/1 'Nunito', sans-serif;
            color: var(--n400);
            margin-top: 2px;
        }

        .inq-ts {
            margin-left: auto;
            font: 500 11px/1 'Nunito', sans-serif;
            color: var(--n300);
        }

        .inq-bd {
            padding: 17px 19px;
            font: 400 italic 13.5px/1.8 'Nunito', sans-serif;
            color: var(--n600);
            border-bottom: 1px solid var(--n100);
            position: relative;
        }

        .inq-bd::before {
            content: '\201C';
            font: 600 44px/1 'Lora', serif;
            color: var(--br-pale);
            position: absolute;
            top: 4px;
            left: 12px;
            line-height: 1;
        }

        .inq-inner {
            padding-left: 16px;
        }

        .inq-ft {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 10px 17px;
            background: var(--n50);
        }

        .inq-tag {
            font: 600 11px/1 'Nunito', sans-serif;
            color: var(--n600);
            background: var(--wh);
            border: 1.5px solid var(--n150);
            border-radius: 20px;
            padding: 3px 11px;
        }

        .inq-tag b {
            color: var(--br);
            font-weight: 800;
        }

        /* ═══════════ OTP ═══════════ */
        .otp {
            text-align: center;
            padding: 26px 20px;
            margin: 4px 0;
            background: var(--n50);
            border: 2px dashed var(--br-pale);
            border-radius: 12px;
        }

        .otp-pre {
            font: 900 10px/1 'Nunito', sans-serif;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--br-tan);
            margin-bottom: 14px;
        }

        .otp-code {
            display: block;
            font: 900 54px/1 'Nunito', sans-serif;
            color: var(--br);
            letter-spacing: 14px;
        }

        .otp-note {
            font: 600 12px/1 'Nunito', sans-serif;
            color: var(--n300);
            margin-top: 12px;
            letter-spacing: .2px;
        }

        .expiry-row {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            margin-top: 14px;
            background: var(--wh);
            border: 1.5px solid var(--n150);
            border-radius: 8px;
        }

        .ex-ico {
            width: 22px;
            height: 22px;
            background: var(--br-tint);
            border: 1px solid var(--br-pale);
            border-radius: 6px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .ex-lbl {
            font: 500 11px/1 'Nunito', sans-serif;
            color: var(--n400);
        }

        .ex-val {
            font: 900 13px/1 'Nunito', sans-serif;
            color: var(--br);
        }

        /* ═══════════ SECURITY CARD ═══════════ */
        .sec-card {
            border: 1.5px solid var(--n150);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 16px;
        }

        .sc-hd {
            background: var(--n50);
            border-bottom: 1px solid var(--n150);
            padding: 11px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sc-ico {
            width: 22px;
            height: 22px;
            background: var(--wh);
            border: 1.5px solid var(--n200);
            border-radius: 6px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .sc-t {
            font: 800 11px/1 'Nunito', sans-serif;
            letter-spacing: .3px;
            color: var(--n600);
        }

        .sc-row {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            padding: 12px 16px;
            border-top: 1px solid var(--n100);
        }

        .sc-dot {
            width: 5px;
            height: 5px;
            background: var(--br-tan);
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 5px;
        }

        .sc-txt {
            font: 400 12.5px/1.6 'Nunito', sans-serif;
            color: var(--n600);
        }

        .sc-txt strong {
            font-weight: 700;
            color: var(--n800);
        }

        /* ═══════════ EVENT LOG ═══════════ */
        .evlog {
            border: 1.5px solid var(--n150);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 16px;
        }

        .evl-hd {
            background: var(--n50);
            border-bottom: 1px solid var(--n150);
            padding: 11px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .evl-title {
            font: 900 9px/1 'Nunito', sans-serif;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--n400);
        }

        .evl-badge {
            font: 800 9px/1 'Nunito', sans-serif;
            color: var(--g-dk);
            background: var(--g-bg);
            border: 1.5px solid var(--g-bd);
            border-radius: 20px;
            padding: 2px 10px;
        }

        .evl-row {
            display: grid;
            grid-template-columns: 110px 1fr;
            border-top: 1px solid var(--n100);
        }

        .evl-lbl {
            padding: 11px 16px;
            background: var(--n50);
            border-right: 1px solid var(--n100);
            font: 700 11px/1 'Nunito', sans-serif;
            color: var(--n400);
        }

        .evl-val {
            padding: 11px 16px;
            font: 500 12.5px/1 'Nunito', sans-serif;
            color: var(--n800);
        }

        /* ═══════════ NOT-YOU / IGNORE ═══════════ */
        .ny {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 15px;
            margin-top: 16px;
            background: var(--r-bg);
            border: 1.5px solid var(--r-bd);
            border-radius: 10px;
        }

        .ny-ico {
            width: 30px;
            height: 30px;
            background: var(--wh);
            border: 1.5px solid var(--r-bd);
            border-radius: 8px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .ny-t {
            font: 800 13px/1 'Nunito', sans-serif;
            color: var(--r-dk);
            margin-bottom: 3px;
        }

        .ny-s {
            font: 400 12px/1.6 'Nunito', sans-serif;
            color: var(--r-dk);
            opacity: .85;
        }

        .ny-s a {
            color: var(--r-md);
            text-decoration: none;
            border-bottom: 1px solid rgba(196, 40, 40, .3);
        }

        .ignore {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            padding: 13px 15px;
            background: var(--n50);
            border: 1.5px solid var(--n150);
            border-radius: 8px;
        }

        .ig-ico {
            width: 26px;
            height: 26px;
            background: var(--wh);
            border: 1.5px solid var(--n200);
            border-radius: 7px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .ig-txt {
            font: 400 12px/1.65 'Nunito', sans-serif;
            color: var(--n400);
        }

        .ig-txt strong {
            font-weight: 700;
            color: var(--n700);
        }

        .ig-txt a {
            color: var(--br-tan);
            border-bottom: 1px solid rgba(154, 122, 58, .3);
            text-decoration: none;
        }

        /* ═══════════ SUB TABLE ═══════════ */
        .sub-table {
            border: 1.5px solid var(--n150);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 16px;
        }

        .st-hd {
            background: var(--n50);
            border-bottom: 1px solid var(--n150);
            padding: 11px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .st-t {
            font: 900 9px/1 'Nunito', sans-serif;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--n400);
        }

        .st-badge {
            font: 800 9px/1 'Nunito', sans-serif;
            color: var(--g-dk);
            background: var(--g-bg);
            border: 1.5px solid var(--g-bd);
            border-radius: 20px;
            padding: 2px 10px;
        }

        .st-row {
            display: grid;
            grid-template-columns: 110px 1fr;
            border-top: 1px solid var(--n100);
        }

        .st-lbl {
            padding: 11px 16px;
            background: var(--n50);
            border-right: 1px solid var(--n100);
            font: 700 11px/1 'Nunito', sans-serif;
            color: var(--n400);
        }

        .st-val {
            padding: 11px 16px;
            font: 500 12.5px/1 'Nunito', sans-serif;
            color: var(--n800);
        }

        /* ═══════════ CTA ═══════════ */
        .cta {
            padding: 26px 30px 30px;
            background: var(--wh);
            border-top: 1px solid var(--n100);
        }

        .btn {
            display: inline-block;
            padding: 14px 30px;
            font: 900 12px/1 'Nunito', sans-serif;
            letter-spacing: .6px;
            text-transform: uppercase;
            text-decoration: none;
            border-radius: 8px;
            transition: opacity .14s, transform .1s;
        }

        .btn:hover {
            opacity: .83;
        }

        .btn:active {
            transform: scale(.97);
        }

        .btn-dark {
            background: var(--br);
            color: #fff;
        }

        .btn-tan {
            background: var(--br-tan);
            color: #fff;
        }

        .btn-ok {
            background: var(--g-md);
            color: #fff;
        }

        .btn-out {
            background: transparent;
            color: var(--br);
            border: 2px solid var(--n200);
        }

        .btn-out:hover {
            border-color: var(--n400);
        }

        .btn-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .cta-ghost {
            display: block;
            margin-top: 10px;
            font: 600 12.5px/1 'Nunito', sans-serif;
            color: var(--n300);
            text-decoration: none;
        }

        .cta-ghost:hover {
            color: var(--br);
        }

        .cta-note {
            font: 400 12px/1.75 'Nunito', sans-serif;
            color: var(--n400);
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid var(--n100);
        }

        .cta-note a {
            color: var(--br-tan);
            border-bottom: 1px solid rgba(154, 122, 58, .28);
            text-decoration: none;
        }

        /* ═══════════ FOOTER ═══════════ */
        .foot {
            background: var(--n50);
            border-top: 1px solid var(--n150);
            padding: 18px 30px;
        }

        .ft-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .ft-wm {
            font: 900 13px/1 'Nunito', sans-serif;
            color: var(--br);
            letter-spacing: -.4px;
        }

        .ft-tag {
            font: 700 8px/1 'Nunito', sans-serif;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: var(--n300);
        }

        .ft-rule {
            height: 1px;
            background: var(--n150);
            margin-bottom: 10px;
        }

        .ft-ls {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            align-items: center;
        }

        .fl {
            font: 600 10.5px/1 'Nunito', sans-serif;
            color: var(--n300);
            text-decoration: none;
        }

        .fl:hover {
            color: var(--br);
        }

        .fd {
            font-size: 9px;
            color: var(--n200);
        }

        svg {
            display: block;
        }

        @media(max-width:600px) {

            .hd-a,
            .hd-b,
            .hd-c,
            .hd-d,
            .h1,
            .h2,
            .h3,
            .h4,
            .h5,
            .h6,
            .sec,
            .cta,
            .foot,
            .sp {
                padding-left: 18px;
                padding-right: 18px;
            }

            .ht {
                font-size: 25px !important;
            }

            .cards3 {
                grid-template-columns: 1fr 1fr;
            }

            .cards2,
            .howgrid,
            .why {
                grid-template-columns: 1fr;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .price-card {
                flex-direction: column;
            }

            .evl-row,
            .st-row {
                grid-template-columns: 1fr;
            }

            .evl-lbl,
            .st-lbl {
                border-right: none;
                border-bottom: 1px solid var(--n100);
            }

            .otp-code {
                font-size: 40px !important;
                letter-spacing: 10px !important;
            }

            .ft-steps {
                flex-direction: column;
                gap: 12px;
            }

            .ft-step:not(:last-child)::after {
                display: none;
            }
        }


        /* Email template overrides */
        body {
            background: #F0F0F0;
            padding: 24px 12px;
        }

        .email-wrap {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background: var(--wh);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, .055), 0 4px 16px rgba(0, 0, 0, .06);
        }
    </style>
</head>

<body>
    <div class="email-wrap">
        <div class="hd-a">
            <img src="{{ $logoUrl }}" width="100" height="40">
            <span class="hd-badge">Buyer Account</span>
        </div>
        <div class="h1">

            <img src="{{ public_url('images/mail/svg/globe-large.svg') }}" width="200" height="200"
                style="opacity: 0.3; position: absolute; top: -10%; right: -3%;">
            <div class="pill-row" style="margin-top: 20px;">
                <div class="mpill pill-buyer"><span class="pdot pd-buyer"></span><span class="pill-txt">Buyer
                        Account</span></div>
            </div>
            <div class="eyebrow">
                <div class="ew-ln"></div><span class="ew-tx">Welcome</span>
            </div>
            <div class="ht">Find the right manufacturers.<br><em>Connect directly.</em></div>
            <div class="hs">Your SourceNest account is active. Search manufacturers, send sourcing requests, and
                manage your supply chain — all in one place, with zero middlemen.</div>
        </div>

        <div class="sec wh">
            <div class="greeting">Dear {{ $firstName  ?? 'There' }},</div>
            <p class="p lead">Welcome to SourceNest — a professional B2B sourcing platform built for buyers who need
                direct, reliable access to manufacturers worldwide.</p>
            <p class="p">You now have full access to search suppliers, send sourcing requests, compare
                manufacturers, and manage all communication from your dashboard. No agents. No commission fees. Just
                direct connections.</p>
            <!-- Value triptych — no fake numbers -->
            <div class="stats">
                <div class="stat">
                    <div class="stat-ico">
                        <img src="{{ public_url('images/mail/svg/search.svg') }}" width="13" height="13">
                    </div>
                    <div class="stat-t">Direct Access</div>
                    <div class="stat-d">Contact manufacturers directly — no agents or commissions</div>
                </div>
                <div class="stat">
                    <div class="stat-ico">
                        <img src="{{ public_url('images/mail/svg/case.svg') }}" width="13" height="13">
                    </div>
                    <div class="stat-t">Simple Requests</div>
                    <div class="stat-d">Send sourcing requests to multiple manufacturers in seconds</div>
                </div>
                <div class="stat">
                    <div class="stat-ico">
                        <img src="{{ public_url('images/mail/svg/globe.svg') }}" width="13" height="13">
                    </div>
                    <div class="stat-t">Global Reach</div>
                    <div class="stat-d">Discover manufacturers across industries and regions worldwide</div>
                </div>
            </div>
        </div>

        <div class="sec gs">
            <div class="sh">
                <div class="sh-bar"></div><span class="sh-txt">How SourceNest <em>works</em></span>
            </div>
            <div class="howgrid">
                <div class="hc">
                    <div class="hc-n">1</div>
                    <div class="hc-t">Search manufacturers</div>
                    <div class="hc-d">Find suppliers by product, region, or industry using our search tools.</div>
                </div>
                <div class="hc">
                    <div class="hc-n">2</div>
                    <div class="hc-t">Review & compare profiles</div>
                    <div class="hc-d">Examine product listings, capabilities, and lead times before deciding.</div>
                </div>
                <div class="hc">
                    <div class="hc-n">3</div>
                    <div class="hc-t">Send a direct inquiry</div>
                    <div class="hc-d">Contact manufacturers through SourceNest — no cold emails or agents.</div>
                </div>
                <div class="hc">
                    <div class="hc-n">4</div>
                    <div class="hc-t">Build your supply chain</div>
                    <div class="hc-d">Compare responses, negotiate terms, and finalize your sourcing decisions.</div>
                </div>
            </div>
        </div>

        <div class="sec wh">
            <div class="sh">
                <div class="sh-bar"></div><span class="sh-txt">What you can do <em>today</em></span>
            </div>
            <div class="vstrip">
                <div class="vr">
                    <div class="vr-ico">
                        <img src="{{ public_url('images/mail/svg/search.svg') }}" width="13" height="13">
                    </div>
                    <div>
                        <div class="vr-t">Discover and compare manufacturers</div>
                        <div class="vr-d">Browse manufacturer profiles filtered by product, category, or region.
                            Build shortlists instantly.</div>
                    </div>
                </div>
                <div class="vr">
                    <div class="vr-ico">
                        <img src="{{ public_url('images/mail/svg/case.svg') }}" width="13" height="13">
                    </div>
                    <div>
                        <div class="vr-t">Send sourcing requests with ease</div>
                        <div class="vr-d">Reach manufacturers directly with your requirements, volume, and timeline.
                        </div>
                    </div>
                </div>
                <div class="vr">
                    <div class="vr-ico">
                        <img src="{{ public_url('images/mail/svg/carriculam.svg') }}" width="13" height="13">
                    </div>
                    <div>
                        <div class="vr-t">Manage all communication in one place</div>
                        <div class="vr-d">Every conversation and supplier interaction organized in your SourceNest
                            dashboard.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sec gs">
            <div class="sh">
                <div class="sh-bar"></div><span class="sh-txt">Your first <em>three steps</em></span>
            </div>
            <div class="steps">
                <div class="step">
                    <div class="step-nc">
                        <div class="step-n">1</div>
                        <div class="step-conn"></div>
                    </div>
                    <div>
                        <div class="step-t">Run your first search</div>
                        <div class="step-d">Go to SourceNest and search by product name, material, or industry. Start
                            specific — you can always broaden your search.</div>
                    </div>
                </div>
                <div class="step">
                    <div class="step-nc">
                        <div class="step-n">2</div>
                        <div class="step-conn"></div>
                    </div>
                    <div>
                        <div class="step-t">Review and shortlist manufacturers</div>
                        <div class="step-d">Open profiles, review production capacity and listings, and shortlist the
                            suppliers that match your requirements.</div>
                    </div>
                </div>
                <div class="step">
                    <div class="step-nc">
                        <div class="step-n">3</div>
                        <div class="step-conn"></div>
                    </div>
                    <div>
                        <div class="step-t">Send your first direct inquiry</div>
                        <div class="step-d">Contact shortlisted suppliers through the platform. State your
                            requirements, volume, and timeline clearly.</div>
                    </div>
                </div>
            </div>
            <div class="alert brand">
                <div class="al">Pro Tip</div>
                <div class="ab">Contact multiple suppliers for any sourcing requirement. This gives you options to
                    compare pricing, quality, and lead times before committing to a manufacturing partner.</div>
            </div>
        </div>

        <div class="cta">
            <a href="{{ config('app.frontend_url') }}" class="btn btn-tan">Explore the Platform</a>
            <a href="{{ config('app.frontend_url') }}/suppliers" class="cta-ghost">Browse the supplier directory →</a>
            <div class="cta-note">Questions? <a href="mailto:support@sourcenest.com">support@sourcenest.com</a></div>
        </div>
        <div class="foot">
            <div class="ft-top"><span class="ft-wm">sourcenest</span><span class="ft-tag">Global Sourcing
                    Platform</span></div>
            <div class="ft-rule"></div>
            <div class="ft-ls"><a href="{{ config('app.frontend_url') }}" class="fl">Unsubscribe</a><span class="fd">·</span><a
                    href="{{ config('app.frontend_url') }}" class="fl">Preferences</a><span class="fd">·</span><a
                    href="{{ config('app.frontend_url') }}/privacy" class="fl">Privacy</a><span class="fd">·</span><a href="{{ config('app.frontend_url') }}/terms"
                    class="fl">Terms</a></div>
        </div>
    </div>


</body>

</html>
