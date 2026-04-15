<?php
require_once __DIR__ . '/session.php';
require_login();
session_write_close();
$username = get_username();
$role = is_admin() ? 'Admin' : 'Collaborator';

$target = '';
if (is_admin() && !empty($_GET['target'])) {
    $target = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($_GET['target']));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PITS — File Manager</title>
    <meta name="description" content="PITS Student Archival System — File Manager">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="background.css">
    <style>
        /* ========== DESIGN TOKENS ========== */
        :root {
            --bg-dark: #0a100a;
            --text-primary: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.65);
            --text-cream: #e8e0d0;
            --glass-bg: rgba(55, 70, 50, 0.30);
            --glass-border: rgba(255, 255, 255, 0.06);
            --glass-blur: 20px;
            --input-bg: rgba(25, 35, 22, 0.75);
            --input-border: rgba(255, 255, 255, 0.08);
            --btn-bg: rgba(225, 218, 200, 0.85);
            --btn-text: #2a2a2a;
            --font-heading: 'Libre Baskerville', Georgia, serif;
            --font-label: 'DM Sans', 'Helvetica Neue', sans-serif;
            --radius-card: 16px;
            --radius-inner: 12px;
            --radius-pill: 30px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            color: var(--text-primary);
            font-family: var(--font-label);
            overflow-x: hidden;
        }

        /* TOP BAR */
        .top-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 28px 44px;
            z-index: 100;
            font-family: var(--font-heading);
            font-size: 1rem;
            color: var(--text-cream);
            transition: all 0.45s cubic-bezier(0.16, 1, 0.3, 1);
            animation: topSlide 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .top-bar .greeting {
            font-size: 1rem;
            font-weight: 400;
            transition: opacity 0.3s ease;
        }

        .top-bar.scrolled {
            top: 12px;
            left: 50%;
            right: auto;
            transform: translateX(-50%);
            width: auto;
            max-width: 600px;
            padding: 12px 28px;
            background: rgba(10, 18, 10, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 50px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.04);
            font-size: 0.88rem;
            gap: 20px;
            animation: pillFloat 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .top-bar.scrolled .greeting {
            font-size: 0.85rem;
            opacity: 1;
        }

        @keyframes topSlide {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pillFloat {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-15px) scale(0.9);
                filter: blur(4px);
            }

            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0) scale(1);
                filter: blur(0);
            }
        }

        /* LAYOUT */
        .page-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding: 90px 3vw 50px;
        }

        /* STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(240px, 100%), 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            width: 100%;
            max-width: 1100px;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-card);
            padding: 1.5rem;
            text-align: left;
            position: relative;
            overflow: hidden;
            animation: cardFloat 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
            transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s cubic-bezier(0.22, 1, 0.36, 1), border-color 0.35s ease;
        }

        .stat-card:nth-child(1) {
            animation-delay: 0.05s;
        }

        .stat-card:nth-child(2) {
            animation-delay: 0.12s;
        }

        .stat-card:nth-child(3) {
            animation-delay: 0.19s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.12);
        }

        .stat-card .label {
            font-family: var(--font-label);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-family: var(--font-heading);
            font-size: 1.8rem;
            color: var(--text-primary);
        }

        .stat-chart-mini {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 100px;
            height: 60px;
            opacity: 0.3;
        }

        /* GLASS CARD — DASHBOARD */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            animation: cardFloat 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
            transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s cubic-bezier(0.22, 1, 0.36, 1), border-color 0.35s ease;
        }

        .dashboard-card {
            width: 100%;
            max-width: 1100px;
            padding: 2.5rem;
            min-height: 600px;
            display: flex;
            flex-direction: column;
        }

        @keyframes cardFloat {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
                filter: blur(4px);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
                filter: blur(0);
            }
        }

        /* DASH HEADER */
        .dash-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 0 1rem 0;
            margin-bottom: 0;
        }

        .user-info {
            font-size: 0.88rem;
        }

        .user-info span {
            font-weight: 700;
        }

        .logout-link {
            color: var(--text-cream);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .logout-link:hover {
            opacity: 1;
        }

        /* DASH BODY */
        .dash-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* QUOTA BAR */
        .quota-bar {
            width: 100%;
            height: 5px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 3px;
            margin-bottom: 6px;
            overflow: hidden;
        }

        .quota-fill {
            height: 100%;
            background: rgba(160, 200, 140, 0.6);
            border-radius: 3px;
            width: 0%;
            transition: width 0.4s;
            position: relative;
            overflow: hidden;
        }

        .quota-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0% {
                left: -100%;
            }

            100% {
                left: 200%;
            }
        }

        .quota-text {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-bottom: 14px;
        }

        /* QUOTA WARNING */
        .quota-warning {
            background: rgba(255, 180, 50, 0.12);
            border: 1px solid rgba(255, 180, 50, 0.35);
            border-radius: var(--radius-inner);
            padding: 12px 18px;
            margin-bottom: 1.2rem;
            display: none;
            align-items: center;
            gap: 10px;
            font-family: var(--font-label);
            font-size: 0.8rem;
            color: #ffc857;
            animation: warningPulse 2s ease-in-out infinite;
        }

        .quota-warning.visible {
            display: flex;
        }

        .quota-warning svg {
            width: 18px;
            height: 18px;
            stroke: #ffc857;
            fill: none;
            flex-shrink: 0;
        }

        @keyframes warningPulse {

            0%,
            100% {
                border-color: rgba(255, 180, 50, 0.35);
            }

            50% {
                border-color: rgba(255, 180, 50, 0.7);
            }
        }

        /* TOOLBAR */
        .toolbar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .btn-tool {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            padding: 8px 16px;
            border-radius: var(--radius-inner);
            font-family: var(--font-label);
            font-size: 0.82rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .btn-tool:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-tool:active {
            transform: scale(0.95);
        }

        .btn-tool svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
        }

        /* SORT BAR */
        .sort-bar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-family: var(--font-label);
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        .sort-bar span {
            opacity: 0.6;
        }

        .sort-btn {
            background: none;
            border: 1px solid transparent;
            color: var(--text-muted);
            font-family: var(--font-label);
            font-size: 0.72rem;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .sort-btn:hover,
        .sort-btn.active {
            color: var(--text-cream);
            border-color: rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.05);
        }

        .sort-btn.active::after {
            content: ' ▾';
            font-size: 0.6rem;
        }

        /* FILE LIST */
        .file-list-container {
            background: rgba(15, 22, 14, 0.45);
            border: 1px solid var(--input-border);
            border-radius: 10px;
            flex: 1;
            min-height: 250px;
            overflow-y: auto;
        }

        .file-list-container::-webkit-scrollbar {
            width: 5px;
        }

        .file-list-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .file-list-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 3px;
        }

        .file-row {
            display: flex;
            align-items: center;
            padding: 14px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            transition: background 0.15s;
            animation: rowSlide 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .file-row:last-child {
            border-bottom: none;
        }

        .file-row:hover {
            background: rgba(160, 200, 140, 0.04) !important;
            box-shadow: inset 2px 0 0 rgba(160, 200, 140, 0.4);
        }

        @keyframes rowSlide {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .file-row:nth-child(1) {
            animation-delay: 0.02s;
        }

        .file-row:nth-child(2) {
            animation-delay: 0.06s;
        }

        .file-row:nth-child(3) {
            animation-delay: 0.10s;
        }

        .file-row:nth-child(4) {
            animation-delay: 0.14s;
        }

        .file-row:nth-child(5) {
            animation-delay: 0.18s;
        }

        .file-checkbox {
            width: 16px;
            height: 16px;
            margin-right: 16px;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.25);
            background: transparent;
            appearance: none;
            -webkit-appearance: none;
            border-radius: 3px;
            position: relative;
            flex-shrink: 0;
        }

        .file-checkbox:checked {
            background: rgba(160, 200, 140, 0.6);
            border-color: rgba(160, 200, 140, 0.6);
        }

        .file-checkbox:checked::after {
            content: '✓';
            position: absolute;
            color: #1a2a18;
            font-size: 11px;
            font-weight: bold;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .file-icon-svg {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            flex-shrink: 0;
            opacity: 0.8;
        }

        .file-name {
            font-size: 0.9rem;
            color: var(--text-primary);
            flex: 1;
            word-break: break-all;
            cursor: pointer;
        }

        .file-size {
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-left: 16px;
            white-space: nowrap;
        }

        .btn-mini {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
            font-family: var(--font-label);
            font-size: 0.68rem;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            margin-left: 12px;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .btn-mini:hover {
            background: rgba(255, 255, 255, 0.12);
            color: var(--text-primary);
        }

        .empty-msg {
            padding: 60px 20px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* STATUS MSG */
        .status-msg {
            font-size: 0.8rem;
            margin-top: 8px;
            min-height: 1.2em;
            transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .status-msg.error {
            color: #e74c3c;
        }

        .status-msg.success {
            color: #7dcea0;
        }

        /* ANALYTICS */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(280px, 100%), 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
            width: 100%;
        }

        .analytics-card {
            background: rgba(15, 22, 14, 0.5);
            border: 1px solid var(--input-border);
            border-radius: var(--radius-inner);
            padding: 1.5rem;
            transition: border-color 0.35s ease;
        }

        .analytics-card:hover {
            border-color: rgba(255, 255, 255, 0.12);
        }

        .analytics-card h3 {
            font-family: var(--font-heading);
            font-size: 1rem;
            font-weight: 400;
            margin-bottom: 1rem;
            color: var(--text-cream);
        }

        .largest-file {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            font-family: var(--font-label);
            font-size: 0.8rem;
        }

        .largest-file:last-child {
            border-bottom: none;
        }

        .largest-file .fname {
            color: var(--text-primary);
        }

        .largest-file .fsize {
            color: var(--text-muted);
            font-size: 0.72rem;
        }

        .weekly-chart {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            height: 120px;
            padding-top: 10px;
        }

        .chart-bar-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            height: 100%;
            justify-content: flex-end;
        }

        .chart-bar {
            width: 100%;
            max-width: 30px;
            border-radius: 4px 4px 0 0;
            background: linear-gradient(to top, rgba(160, 200, 140, 0.3), rgba(160, 200, 140, 0.7));
            transition: height 0.6s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .chart-label {
            font-family: var(--font-label);
            font-size: 0.6rem;
            color: var(--text-muted);
        }

        /* MODAL */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-overlay.active .modal-window {
            animation: modalPop 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes modalPop {
            from {
                opacity: 0;
                transform: scale(0.85) translateY(20px);
                filter: blur(8px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
                filter: blur(0);
            }
        }

        .modal-window {
            background: rgba(15, 25, 15, 0.95);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-card);
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .modal-window h2 {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .input-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .input-group label {
            display: block;
            font-family: var(--font-label);
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 2.2px;
            text-transform: uppercase;
            color: var(--text-cream);
            margin-bottom: 10px;
        }

        .input-group input {
            width: 100%;
            padding: 14px 18px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: var(--radius-inner);
            font-family: var(--font-label);
            font-size: 0.92rem;
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.25s, background 0.25s;
        }

        .input-group input:focus {
            border-color: rgba(160, 200, 140, 0.4) !important;
            background: rgba(25, 35, 22, 0.9);
        }

        .btn-submit {
            display: block;
            padding: 12px 30px;
            background: var(--btn-bg);
            color: var(--btn-text);
            border: none;
            border-radius: var(--radius-pill);
            font-family: var(--font-label);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.35);
        }

        /* UPLOAD PROGRESS */
        .upload-prog {
            display: none;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .upload-track {
            width: 100%;
            height: 3px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }

        .upload-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, rgba(160, 200, 140, 0.7), rgba(200, 220, 160, 0.9));
            border-radius: 2px;
            transition: width 0.2s;
        }

        /* STAFF REQUESTS SECTION */
        .staff-requests-section {
            width: 100%; max-width: 1100px;
            margin-top: 2rem;
            animation: cardFloat 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
            animation-delay: 0.3s;
        }
        .staff-requests-section .section-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1rem;
        }
        .staff-requests-section .section-header h2 {
            font-family: var(--font-heading); font-weight: 400; font-size: 1.4rem;
            color: var(--text-cream);
        }
        .staff-requests-section .req-count {
            font-size: 0.78rem; color: rgba(160,200,140,0.9);
            background: rgba(160,200,140,0.12); padding: 4px 12px; border-radius: 20px;
        }
        .staff-tiles-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(min(280px, 100%), 1fr));
            gap: 1rem;
        }
        .staff-tile {
            background: rgba(15, 22, 14, 0.5); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px; padding: 1.3rem;
            transition: border-color 0.3s, transform 0.3s;
            animation: tileSlideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .staff-tile:hover { border-color: rgba(160,200,140,0.2); transform: translateY(-2px); }
        @keyframes tileSlideUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .staff-tile .st-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.6rem; }
        .staff-tile .st-title { font-family: var(--font-heading); font-size: 1rem; font-weight: 400; color: var(--text-cream); }
        .staff-tile .st-from { font-size: 0.72rem; color: #a0c88c; }
        .staff-tile .st-desc { font-size: 0.8rem; color: var(--text-muted); line-height: 1.4; margin-bottom: 0.8rem; }
        .staff-tile .st-status { font-size: 0.72rem; margin-bottom: 0.8rem; }
        .staff-tile .st-status .shared { color: #7dcea0; }
        .staff-tile .st-status .pending { color: #ffc857; }
        .staff-tile .st-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(160,200,140,0.12); border: 1px solid rgba(160,200,140,0.2);
            color: rgba(160,200,140,0.9);
            padding: 6px 14px; border-radius: 8px; font-size: 0.78rem;
            font-family: var(--font-label); cursor: pointer;
            transition: all 0.25s;
        }
        .staff-tile .st-btn:hover { background: rgba(160,200,140,0.2); border-color: rgba(160,200,140,0.4); }
        .staff-tile .st-btn svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2; }
        .staff-tile .st-btn.done { background: rgba(125,206,160,0.12); border-color: rgba(125,206,160,0.2); color: #7dcea0; cursor: default; }

        /* SHARE MODAL */
        .share-modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);
            z-index: 1100; display: none;
            justify-content: center; align-items: center;
            opacity: 0; transition: opacity 0.3s;
        }
        .share-modal-overlay.active { display: flex; opacity: 1; }
        .share-modal-overlay.active .modal-window { animation: modalPop 0.4s cubic-bezier(0.16, 1, 0.3, 1) both; }
        .share-prog { display: none; margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-muted); }
        .share-track { width: 100%; height: 3px; background: rgba(255,255,255,0.08); border-radius: 2px; margin-top: 5px; overflow: hidden; }
        .share-bar { height: 100%; width: 0%; background: linear-gradient(90deg, rgba(160,200,140,0.7), rgba(200,220,160,0.9)); border-radius: 2px; transition: width 0.2s; }


        /* RESPONSIVE */
        @media(max-width:1200px) {
            .page-wrapper {
                padding: 90px 2vw 50px;
            }
        }

        @media(max-width:768px) {
            .top-bar {
                padding: 16px 20px;
                font-size: 0.82rem;
            }

            .top-bar.greeting {
                display: none;
            }

            .top-bar.scrolled {
                max-width: 90vw;
                padding: 10px 20px;
                font-size: 0.78rem;
                gap: 14px;
                top: 8px;
            }

            .page-wrapper {
                padding: 80px 12px 60px;
            }

            .dashboard-card {
                padding: 1.5rem;
                min-height: auto;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(min(180px, 100%), 1fr));
                gap: 1rem;
            }

            .stat-card .value {
                font-size: 1.5rem;
            }

            .toolbar {
                gap: 0.6rem;
            }

            .btn-tool {
                font-size: 0.75rem;
                padding: 6px 12px;
            }

            .file-row {
                padding: 12px 16px;
            }
        }

        @media(max-width:600px) {
            .top-bar {
                top: 0;
                left: 0;
                right: 0;
                transform: none;
                width: 100%;
                max-width: none;
                padding: 12px 20px;
                background: rgba(10, 18, 10, 0.9);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 0;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
                font-size: 0.85rem;
                justify-content: space-between;
                gap: 5px;
            }
            .top-bar.scrolled {
                top: 10px;
                left: 10px;
                right: 10px;
                transform: none;
                width: calc(100% - 20px);
                max-width: none;
                border-radius: 50px;
                border: 1px solid rgba(255, 255, 255, 0.08);
            }
            .page-wrapper {
                padding: 90px 4vw 60px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }

            .toolbar {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .btn-tool {
                flex: 1 1 calc(50% - 0.5rem);
                justify-content: center;
            }
        }

        @media(max-width:420px) {
            .top-bar {
                padding: 12px 16px;
            }

            .page-wrapper {
                padding: 64px 6px 65px;
            }

            .dashboard-card {
                padding: 1rem;
            }

            .btn-tool {
                flex: 1 1 100%;
            }
        }
    </style>
</head>

<body>

    <div class="top-bar" id="topBar">
        <span id="currentDate"></span>
        <span class="greeting" id="greeting">Welcome back, <?php echo htmlspecialchars($username); ?></span>
        <span id="currentTime"></span>
    </div>

    <div class="page-wrapper">

        <!-- STATS GRID -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Storage</div>
                <div class="value" id="statTotal">200 MB</div>
                <div class="stat-chart-mini"><svg viewBox="0 0 100 60">
                        <path d="M0,60 Q25,30 50,45 T100,20" stroke="var(--text-cream)" fill="none" />
                    </svg></div>
            </div>
            <div class="stat-card">
                <div class="label">Used Space</div>
                <div class="value" id="statUsed">—</div>
                <div class="stat-chart-mini"><svg viewBox="0 0 100 60">
                        <rect x="10" y="40" width="10" height="20" fill="var(--text-muted)" opacity="0.5" />
                        <rect x="30" y="25" width="10" height="35" fill="var(--text-muted)" opacity="0.5" />
                        <rect x="50" y="15" width="10" height="45" fill="var(--text-muted)" opacity="0.5" />
                        <rect x="70" y="30" width="10" height="30" fill="var(--text-muted)" opacity="0.5" />
                    </svg></div>
            </div>
            <div class="stat-card">
                <div class="label">Files Count</div>
                <div class="value" id="statCount">—</div>
            </div>
        </div>

        <!-- DASHBOARD CARD -->
        <div class="glass-card dashboard-card">
            <div class="dash-header">
                <div class="greeting" style="font-family:var(--font-heading); font-size:1rem; color:var(--text-cream); font-weight:400; letter-spacing:0.5px;">
                    <?= $target ? "⚠️ ADMIN CONTROL OVERRIDE ⚠️" : "Hello again" ?>
                </div>
                <div style="display:flex; align-items:center; gap:14px;">
                    <span style="color:var(--text-muted); font-size:0.8rem; font-family:var(--font-label);">
                        <?= htmlspecialchars($target ?: $username) ?> <?= $target ? '(Target)' : "($role)" ?>
                    </span>
                    <a href="logout.php" style="display:inline-flex; align-items:center; gap:6px; padding:8px 20px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.10); border-radius:var(--radius-pill); color:var(--text-cream); text-decoration:none; font-family:var(--font-label); font-size:0.82rem; font-weight:500; backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); transition:all 0.25s ease;"
                       onmouseover="this.style.background='rgba(255,255,255,0.12)'; this.style.borderColor='rgba(255,255,255,0.20)'; this.style.transform='translateY(-1px)';"
                       onmouseout="this.style.background='rgba(255,255,255,0.06)'; this.style.borderColor='rgba(255,255,255,0.10)'; this.style.transform='translateY(0)';">Logout</a>
                </div>
            </div>
            <div class="dash-body">

                <!-- Quota warning -->
                <div class="quota-warning" id="quotaWarning">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span id="quotaWarnText">Storage usage above 80%! Consider cleaning up old files.</span>
                </div>

                <!-- Quota bar -->
                <div class="quota-bar">
                    <div class="quota-fill" id="quotaFill" style="width:0%"></div>
                </div>
                <div class="quota-text" id="quotaText">Calculating…</div>

                <!-- Toolbar -->
                <div class="toolbar">
                    <button class="btn-tool" onclick="triggerUpload()">
                        <svg>
                            <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>Upload
                    </button>
                    <button class="btn-tool" onclick="deleteSelected()">
                        <svg>
                            <path
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>Delete Selected
                    </button>
                    <button class="btn-tool" onclick="downloadSelected()">
                        <svg>
                            <path
                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>Download
                    </button>
                </div>

                <!-- Sort bar -->
                <div class="sort-bar">
                    <span>Sort:</span>
                    <button class="sort-btn active" onclick="sortFiles('name',this)">Name</button>
                    <button class="sort-btn" onclick="sortFiles('size',this)">Size</button>
                    <button class="sort-btn" onclick="sortFiles('date',this)">Date</button>
                </div>

                <!-- File list -->
                <div class="file-list-container" id="fileList">
                    <div class="empty-msg">Loading files…</div>
                </div>

                <!-- Upload progress -->
                <div class="upload-prog" id="uploadProg">
                    Uploading… <span id="uploadPct"></span>
                    <div class="upload-track">
                        <div class="upload-bar" id="uploadBar"></div>
                    </div>
                </div>

                <div class="status-msg" id="statusMsg"></div>
                <input type="file" id="fileInput" style="display:none" onchange="uploadFile(this)">

                <!-- Analytics -->
                <div class="analytics-grid">
                    <div class="analytics-card">
                        <h3>Largest Files</h3>
                        <div id="largestFiles"></div>
                    </div>
                    <div class="analytics-card">
                        <h3>Weekly Usage Growth</h3>
                        <div class="weekly-chart" id="weeklyChart"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STAFF REQUESTS SECTION -->
        <div class="staff-requests-section" id="staffRequestsSection" style="display:none;">
            <div class="section-header">
                <h2>Staff Requests</h2>
                <span class="req-count" id="reqCount">0 requests</span>
            </div>
            <div class="staff-tiles-grid" id="staffTilesGrid">
            </div>
        </div>
    </div>

    <!-- Share File Modal -->
    <div id="shareModal" class="share-modal-overlay" onclick="closeShareModal()">
        <div class="modal-window" onclick="event.stopPropagation()">
            <h2>Share Files</h2>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1rem;" id="shareModalDesc">Upload files to share with the requesting staff member.</p>
            <div style="display:flex;gap:1rem;margin-top:1rem;">
                <button class="btn-submit" onclick="triggerShareUpload()" style="background:var(--btn-bg); color:var(--btn-text); border:none; border-radius:30px; padding:10px 24px; font-family:var(--font-label); font-weight:600; font-size:0.85rem; cursor:pointer;">Choose File</button>
                <button class="btn-tool" onclick="closeShareModal()">Cancel</button>
            </div>
            <div class="share-prog" id="shareProg">
                Uploading... <span id="sharePct"></span>
                <div class="share-track"><div class="share-bar" id="shareBar"></div></div>
            </div>
            <div class="status-msg" id="shareStatus" style="margin-top:8px;"></div>
            <input type="file" id="shareFileInput" style="display:none" onchange="doShareUpload(this)">
        </div>
    </div>

    <!-- Rename modal -->
    <div id="renameModal" class="modal-overlay" onclick="closeModal('renameModal')">
        <div class="modal-window" onclick="event.stopPropagation()">
            <h2>Rename File</h2>
            <div class="input-group">
                <label>New Name</label>
                <input type="text" id="renameInput">
            </div>
            <div style="display:flex;gap:1rem;margin-top:1.5rem;">
                <button class="btn-submit" onclick="doRename()">Rename</button>
                <button class="btn-tool" onclick="closeModal('renameModal')">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // ── Clock ──────────────────────────────────────────────────────────────
        function updateDateTime() {
            var now = new Date();
            var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            document.getElementById('currentDate').textContent = months[now.getMonth()] + ' ' + now.getDate() + ', ' + now.getFullYear();
            var h = now.getHours(), m = now.getMinutes(), ap = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            document.getElementById('currentTime').textContent = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ap;
        }
        updateDateTime(); setInterval(updateDateTime, 1000);


        // ── Scroll-based top bar pill ──────────────────────────────────────────
        var topBar = document.getElementById('topBar');
        window.addEventListener('scroll', function () {
            if (window.scrollY > 60) { topBar.classList.add('scrolled'); }
            else { topBar.classList.remove('scrolled'); }
        });

        // ── Data & State ──────────────────────────────────────────────────────
        var allFiles = [];
        var currentSort = 'name';
        var renameTarget = '';

        function fmtBytes(b) { if (b < 1024) return b + ' B'; if (b < 1048576) return (b / 1024).toFixed(1) + ' KB'; return (b / 1048576).toFixed(1) + ' MB'; }
        function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
        function getIconPath(ext) {
            var img = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
            var vid = ['mp4', 'mkv', 'avi', 'mov', 'webm'];
            var doc = ['pdf', 'doc', 'docx', 'odt', 'pptx', 'ppt', 'xls', 'xlsx'];
            var arc = ['zip', 'rar', '7z', 'tar', 'gz', 'bz2'];
            if (img.includes(ext)) return { p: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', c: '#90caf9' };
            if (vid.includes(ext)) return { p: 'M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z', c: '#f48fb1' };
            if (doc.includes(ext)) return { p: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z', c: '#a5d6a7' };
            if (arc.includes(ext)) return { p: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', c: '#ffd54f' };
            return { p: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z', c: '#80cbc4' };
        }

        // ── Load files ─────────────────────────────────────────────────────────
        function loadFiles() {
            fetch('file_manager.php')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) { showStatus(data.error, 'error'); return; }
                    allFiles = data.files || [];
                    // Update stat cards
                    document.getElementById('statUsed').textContent = fmtBytes(data.usage || 0);
                    document.getElementById('statCount').textContent = allFiles.length;
                    // Quota
                    var pct = parseFloat(data.usage_percent) || 0;
                    document.getElementById('quotaFill').style.width = pct + '%';
                    document.getElementById('quotaText').textContent = fmtBytes(data.usage) + ' / ' + fmtBytes(data.quota) + ' used (' + pct + '%)';
                    var warn = document.getElementById('quotaWarning');
                    if (pct >= 80) { warn.classList.add('visible'); document.getElementById('quotaWarnText').textContent = 'Storage usage at ' + pct.toFixed(0) + '%! Consider cleaning up old files.'; }
                    else { warn.classList.remove('visible'); }
                    renderFileList();
                    renderLargestFiles();
                    renderWeeklyChart(pct);
                })
                .catch(function () { showStatus('Failed to load files', 'error'); });
        }

        // ── Render file list ───────────────────────────────────────────────────
        function renderFileList() {
            var sorted = allFiles.slice();
            if (currentSort === 'name') sorted.sort(function (a, b) { return a.name.localeCompare(b.name); });
            else if (currentSort === 'size') sorted.sort(function (a, b) { return b.size - a.size; });
            else if (currentSort === 'date') sorted.sort(function (a, b) { return (b.modified || 0) - (a.modified || 0); });
            var list = document.getElementById('fileList');
            if (!sorted.length) { list.innerHTML = '<div class="empty-msg">No files yet — upload something!</div>'; return; }
            var html = '';
            sorted.forEach(function (f, i) {
                var ext = (f.type || f.name.split('.').pop() || '').toLowerCase();
                var ic = getIconPath(ext);
                var delay = (i * 0.04).toFixed(2);
                html += '<div class="file-row" style="animation-delay:' + delay + 's">';
                html += '<input type="checkbox" class="file-checkbox" data-name="' + esc(f.name) + '">';
                html += '<svg class="file-icon-svg" viewBox="0 0 24 24" fill="none" stroke="' + ic.c + '" stroke-width="2"><path d="' + ic.p + '"/></svg>';
                html += '<span class="file-name">' + esc(f.name) + '</span>';
                html += '<span class="file-size">' + fmtBytes(f.size) + '</span>';
                html += '<button class="btn-mini" onclick="openRename(\'' + esc(f.name) + '\')">Rename</button>';
                html += '</div>';
            });
            list.innerHTML = html;
        }

        function sortFiles(by, btn) {
            currentSort = by;
            document.querySelectorAll('.sort-btn').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            renderFileList();
        }

        // ── Largest Files ─────────────────────────────────────────────────────
        function renderLargestFiles() {
            var sorted = allFiles.slice().sort(function (a, b) { return b.size - a.size; }).slice(0, 5);
            var html = '';
            if (!sorted.length) { html = '<div style="color:var(--text-muted);font-size:0.8rem;">No files yet.</div>'; }
            sorted.forEach(function (f) {
                html += '<div class="largest-file"><span class="fname">' + esc(f.name) + '</span><span class="fsize">' + fmtBytes(f.size) + '</span></div>';
            });
            document.getElementById('largestFiles').innerHTML = html;
        }

        // ── Weekly chart (visual only, based on current quota %) ─────────────
        function renderWeeklyChart(pct) {
            pct = pct || 0;
            var days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            var base = Math.max(pct - 30, 5);
            var vals = [base * 0.55, base * 0.65, base * 0.75, base * 0.82, base * 0.88, base * 0.94, pct];
            var max = Math.max.apply(null, vals);
            var html = '';
            vals.forEach(function (v, i) {
                var h = (v / max * 100).toFixed(0);
                html += '<div class="chart-bar-wrap"><div class="chart-bar" style="height:' + h + '%"></div><span class="chart-label">' + days[i] + '</span></div>';
            });
            document.getElementById('weeklyChart').innerHTML = html;
        }

        // ── Upload ─────────────────────────────────────────────────────────────
        function triggerUpload() { document.getElementById('fileInput').click(); }
        function uploadFile(input) {
            if (!input.files[0]) return;
            var fd = new FormData(); fd.append('file', input.files[0]);
            var prog = document.getElementById('uploadProg');
            var bar = document.getElementById('uploadBar');
            var pct = document.getElementById('uploadPct');
            prog.style.display = 'block'; bar.style.width = '0%';
            showStatus('Uploading…', '');
            var xhr = new XMLHttpRequest();
            xhr.upload.onprogress = function (e) { if (e.lengthComputable) { var p = Math.round(e.loaded / e.total * 100); bar.style.width = p + '%'; pct.textContent = p + '%'; } };
            xhr.onload = function () {
                prog.style.display = 'none';
                try { var d = JSON.parse(xhr.responseText); if (d.error) { showStatus(d.error, 'error'); } else { showStatus('✓ Uploaded: ' + d.filename, 'success'); loadFiles(); } }
                catch (e) { showStatus('Upload error', 'error'); }
                input.value = '';
            };
            xhr.onerror = function () { prog.style.display = 'none'; showStatus('Upload failed', 'error'); };
            xhr.open('POST', 'upload.php' + (TARGET ? '?target=' + TARGET : '')); xhr.send(fd);
        }

        // ── Delete ─────────────────────────────────────────────────────────────
        function deleteSelected() {
            var checked = document.querySelectorAll('.file-checkbox:checked');
            if (!checked.length) { showStatus('Select files to delete first', 'error'); return; }
            if (!confirm('Delete ' + checked.length + ' file(s)? This cannot be undone.')) return;
            var names = Array.from(checked).map(function (cb) { return cb.dataset.name; });
            fetch('delete.php' + (TARGET ? '?target=' + TARGET : ''), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ files: names }) })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.deleted && d.deleted.length) showStatus('✓ Deleted ' + d.deleted.length + ' file(s)', 'success');
                    if (d.failed && d.failed.length) showStatus('Failed: ' + d.failed.join(', '), 'error');
                    loadFiles();
                })
                .catch(function () { showStatus('Delete failed', 'error'); });
        }

        // ── Download ──────────────────────────────────────────────────────────
        function downloadSelected() {
            var checked = document.querySelectorAll('.file-checkbox:checked');
            if (!checked.length) { showStatus('Select a file to download first', 'error'); return; }
            checked.forEach(function (cb) { window.open('download.php?file=' + encodeURIComponent(cb.dataset.name) + (TARGET ? '&target=' + TARGET : ''), '_blank'); });
        }

        // ── Rename ────────────────────────────────────────────────────────────
        function openRename(name) { renameTarget = name; document.getElementById('renameInput').value = name; openModal('renameModal'); }
        function doRename() {
            var newName = document.getElementById('renameInput').value.trim();
            if(!newName || newName === renameTarget) { closeModal('renameModal'); return; }
            fetch('rename.php' + (TARGET ? '?target=' + TARGET : ''), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ old_name: renameTarget, new_name: newName }) })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    closeModal('renameModal');
                    if (d.success) { showStatus('✓ Renamed file successfully', 'success'); loadFiles(); }
                    else { showStatus(d.error || 'Rename failed', 'error'); }
                })
                .catch(function () { closeModal('renameModal'); showStatus('Rename failed', 'error'); });
        }
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        // ── Status ────────────────────────────────────────────────────────────
        function showStatus(msg, type) {
            var el = document.getElementById('statusMsg');
            el.textContent = msg; el.className = 'status-msg' + (type ? ' ' + type : '');
            if (type === 'success') setTimeout(function () { el.textContent = ''; el.className = 'status-msg'; }, 4000);
        }

        // ══════════════════════════════════════════════════════════════════════
        // STAFF REQUESTS (Student-Facing)
        // ══════════════════════════════════════════════════════════════════════
        var staffRequests = [];
        var currentShareReqId = '';

        function loadStaffRequests() {
            fetch('student_requests_api.php')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    staffRequests = data.requests || [];
                    renderStaffRequests();
                })
                .catch(function() {}); // silent fail
        }

        function renderStaffRequests() {
            var section = document.getElementById('staffRequestsSection');
            var grid = document.getElementById('staffTilesGrid');
            var countEl = document.getElementById('reqCount');

            if (!staffRequests.length) {
                section.style.display = 'none';
                return;
            }

            section.style.display = 'block';
            countEl.textContent = staffRequests.length + ' request' + (staffRequests.length !== 1 ? 's' : '');

            var html = '';
            staffRequests.forEach(function(r, i) {
                html += '<div class="staff-tile" style="animation-delay:' + (i * 0.08).toFixed(2) + 's">';
                html += '<div class="st-header">';
                html += '<span class="st-title">' + esc(r.title) + '</span>';
                html += '<span class="st-from">from ' + esc(r.staff_display || r.staff) + '</span>';
                html += '</div>';
                if (r.description) html += '<div class="st-desc">' + esc(r.description) + '</div>';
                html += '<div class="st-status">';
                if (r.already_shared) {
                    html += '<span class="shared">Shared: ' + r.shared_files.map(function(f){ return esc(f); }).join(', ') + '</span>';
                } else {
                    html += '<span class="pending">Pending — not yet shared</span>';
                }
                html += '</div>';
                if (r.already_shared) {
                    html += '<button class="st-btn done"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>Shared</button>';
                    html += ' <button class="st-btn" onclick="openShareModal(\'' + esc(r.id) + '\', \'' + esc(r.title) + '\')" style="margin-left:6px;"><svg viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>Add More</button>';
                } else {
                    html += '<button class="st-btn" onclick="openShareModal(\'' + esc(r.id) + '\', \'' + esc(r.title) + '\')"><svg viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>Share Files</button>';
                }
                html += '</div>';
            });
            grid.innerHTML = html;
        }

        // ── Share Modal ────────────────────────────────────────────────────────
        function openShareModal(reqId, title) {
            currentShareReqId = reqId;
            document.getElementById('shareModalDesc').textContent = 'Upload files for: ' + title;
            document.getElementById('shareStatus').textContent = '';
            document.getElementById('shareProg').style.display = 'none';
            document.getElementById('shareModal').classList.add('active');
        }
        function closeShareModal() { document.getElementById('shareModal').classList.remove('active'); currentShareReqId = ''; }
        function triggerShareUpload() { document.getElementById('shareFileInput').click(); }

        function doShareUpload(input) {
            if (!input.files[0] || !currentShareReqId) return;
            var fd = new FormData();
            fd.append('file', input.files[0]);
            fd.append('request_id', currentShareReqId);
            var prog = document.getElementById('shareProg');
            var bar = document.getElementById('shareBar');
            var pct = document.getElementById('sharePct');
            var status = document.getElementById('shareStatus');
            prog.style.display = 'block'; bar.style.width = '0%';
            status.textContent = 'Uploading...';
            status.className = 'status-msg';

            var xhr = new XMLHttpRequest();
            xhr.upload.onprogress = function(e) { if (e.lengthComputable) { var p = Math.round(e.loaded / e.total * 100); bar.style.width = p + '%'; pct.textContent = p + '%'; } };
            xhr.onload = function() {
                prog.style.display = 'none';
                try {
                    var d = JSON.parse(xhr.responseText);
                    if (d.error) { status.textContent = d.error; status.className = 'status-msg error'; }
                    else {
                        status.textContent = 'File shared successfully!';
                        status.className = 'status-msg success';
                        setTimeout(function() { closeShareModal(); loadStaffRequests(); }, 1500);
                    }
                } catch(e) { status.textContent = 'Upload error'; status.className = 'status-msg error'; }
                input.value = '';
            };
            xhr.onerror = function() { prog.style.display = 'none'; status.textContent = 'Upload failed'; status.className = 'status-msg error'; };
            xhr.open('POST', 'student_share.php');
            xhr.send(fd);
        }

        loadFiles();
        loadStaffRequests();
    </script>
</body>

</html>