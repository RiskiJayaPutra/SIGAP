<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="initial-scale=1,user-scalable=no,maximum-scale=1,width=device-width">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        
        <!-- CSS Leaflet & Bawaan -->
        <link rel="stylesheet" href="css/leaflet.css">
        <link rel="stylesheet" href="css/qgis2web.css">
        <link rel="stylesheet" href="css/fontawesome-all.min.css">
        <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />

        <!-- Script Turf.js untuk Analisis Spasial (Filtering Titik dalam Area) -->
        <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
        /* Base Reset — override qgis2web.css overflow:hidden */
        html, body {
            width: 100% !important;
            height: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
            overflow: visible !important;
        }
        
        /* Full-screen map */
        #map { width: 100%; height: 100vh; }

        /* Custom Routing UI */
        .custom-routing-panel {
            position: fixed;
            top: 80px;
            right: 16px;
            width: 320px;
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            border: 1px solid rgba(0,0,0,0.06);
            z-index: 10000;
            display: flex;
            flex-direction: column;
            overflow: visible;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            font-family: 'Inter', sans-serif;
        }
        .custom-routing-panel.hidden {
            transform: translateX(120%);
            opacity: 0;
            pointer-events: none;
        }
        .route-header {
            padding: 16px;
            background: #f8fafc;
            border-radius: 16px 16px 0 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .route-header h3 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
        }
        .route-close {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            border-radius: 50%;
            transition: all 0.2s;
        }
        .route-close:hover { background: #e2e8f0; color: #ef4444; }
        .route-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            position: relative;
        }
        .route-input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        .route-icon {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            position: absolute;
            left: 12px;
            z-index: 2;
        }
        .route-icon.origin { border: 3px solid #3b82f6; background: #fff; }
        .route-icon.dest { border: 3px solid #ef4444; background: #fff; }
        .route-input-group input {
            width: 100%;
            padding: 10px 10px 10px 36px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.85rem;
            outline: none;
            background: #fff;
            transition: all 0.2s;
            color: #334155;
            font-weight: 500;
        }
        .route-input-group input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        .route-line {
            position: absolute;
            left: 29px;
            top: 36px;
            bottom: 36px;
            width: 2px;
            background: #e2e8f0;
            z-index: 1;
        }
        .route-suggestions {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            width: 100%;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border: 1px solid #e2e8f0;
            max-height: 200px;
            overflow-y: auto;
            z-index: 10001;
            display: none;
        }
        .route-suggestions.active { display: block; }
        .route-suggestion-item {
            padding: 10px 12px;
            font-size: 0.8rem;
            color: #334155;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
        }
        .route-suggestion-item:last-child { border-bottom: none; }
        .route-suggestion-item:hover { background: #f8fafc; color: #6366f1; }
        .route-footer {
            padding: 12px 16px;
            display: flex;
            gap: 8px;
            border-top: 1px solid #f1f5f9;
        }
        .route-btn {
            flex: 1;
            padding: 8px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-align: center;
        }
        .route-btn.primary { background: #6366f1; color: #fff; }
        .route-btn.primary:hover { background: #4f46e5; }
        .route-btn.danger { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
        .route-btn.danger:hover { background: #fee2e2; }
        
        .leaflet-routing-container { display: none !important; }

        /* Tooltip Hover Pin */
        .leaflet-tooltip {
            background: rgba(30, 41, 59, 0.95);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 6px 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        .leaflet-tooltip-top:before { border-top-color: rgba(30, 41, 59, 0.95); }

        /* ===== INFO PANEL (Floating Overlay) ===== */
        #info-panel {
            position: fixed !important;
            top: 16px !important;
            left: 16px !important;
            width: 320px !important;
            max-height: calc(100vh - 32px) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            z-index: 9999 !important;
            background: rgba(255,255,255,0.97) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 16px !important;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06) !important;
            border: 1px solid rgba(0,0,0,0.06) !important;
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1), opacity 0.3s cubic-bezier(0.4,0,0.2,1);
            pointer-events: auto !important;
            display: block !important;
            visibility: visible !important;
        }

        #info-panel.panel-hidden {
            transform: translateX(-120%) !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        /* Scrollbar styling */
        #info-panel::-webkit-scrollbar { width: 4px; }
        #info-panel::-webkit-scrollbar-track { background: transparent; }
        #info-panel::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 4px; }

        /* Panel Header */
        .panel-header {
            padding: 20px 20px 16px;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-radius: 16px 16px 0 0;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .panel-header::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -20%;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(99,102,241,0.15);
        }
        .panel-header-title {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            z-index: 1;
        }
        .panel-header-sub {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.6);
            margin-top: 2px;
            position: relative;
            z-index: 1;
            padding-left: 28px;
        }

        /* Close button */
        .panel-close-btn {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
            z-index: 2;
            padding: 0;
        }
        .panel-close-btn:hover {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }

        /* Panel Body */
        .panel-body { padding: 16px 20px 20px; }

        /* Stat cards row */
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }
        .stat-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 8px;
            text-align: center;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .stat-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .stat-card-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.2;
        }
        .stat-card-label {
            font-size: 0.6rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 2px;
        }
        .stat-card.primary {
            background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
            border-color: transparent;
        }
        .stat-card.primary .stat-card-value { color: #fff; }
        .stat-card.primary .stat-card-label { color: rgba(255,255,255,0.7); }

        /* Section label */
        .section-label {
            font-size: 0.65rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 10px;
        }

        /* Category list */
        .kategori-list { display: flex; flex-direction: column; gap: 10px; }
        .kategori-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .kategori-item.zero-count { opacity: 0.35; }
        .kategori-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .kategori-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .kategori-icon svg { width: 16px; height: 16px; }
        .kategori-label {
            flex: 1;
            font-size: 0.8rem;
            font-weight: 500;
            color: #334155;
        }
        .kategori-count {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1e293b;
            min-width: 28px;
            text-align: right;
        }
        /* Progress bar */
        .kategori-bar {
            height: 4px;
            background: #f1f5f9;
            border-radius: 2px;
            overflow: hidden;
            margin-left: 36px;
        }
        .kategori-bar-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 0.4s cubic-bezier(0.4,0,0.2,1);
        }

        /* Demografi grid */
        .demografi-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 16px;
        }
        .demografi-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
        }
        .demografi-label {
            font-size: 0.6rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .demografi-value {
            font-size: 0.85rem;
            font-weight: 700;
            color: #334155;
            margin-top: 2px;
        }

        /* Default state hero */
        .default-hero {
            text-align: center;
            padding: 12px 0 4px;
        }
        .default-hero-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #6366f1, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.1;
        }
        .default-hero-sub {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 4px;
        }

        /* Hint box */
        .hint-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 12px 14px;
            margin-top: 16px;
        }
        .hint-box svg { flex-shrink: 0; color: #0ea5e9; margin-top: 1px; }
        .hint-box p {
            font-size: 0.75rem;
            color: #0369a1;
            line-height: 1.5;
            margin: 0;
        }

        /* Divider */
        .panel-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 14px 0;
        }

        /* Filter controls bar */
        .filter-bar {
            position: fixed;
            top: 16px;
            left: 352px;
            z-index: 1000;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .filter-bar select {
            padding: 8px 32px 8px 12px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(8px);
            font-size: 0.8rem;
            font-weight: 500;
            color: #334155;
            cursor: pointer;
            appearance: none;
            outline: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            font-family: inherit;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M8 11.5l-5-5h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
        }
        .filter-bar select:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .filter-btn {
            padding: 8px 14px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(8px);
            font-size: 0.8rem;
            font-weight: 500;
            color: #334155;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            font-family: inherit;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }
        .filter-btn:hover { background: #fff; border-color: #6366f1; color: #4f46e5; }
        .filter-btn.danger {
            background: #ef4444;
            border-color: transparent;
            color: #fff;
            display: none;
        }
        .filter-btn.danger:hover { background: #dc2626; }

        /* ===== CATEGORY FILTER DROPDOWN (Hover) ===== */
        .cat-filter-wrapper {
            position: relative;
        }
        .cat-filter-trigger {
            padding: 8px 14px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(8px);
            font-size: 0.8rem;
            font-weight: 500;
            color: #334155;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            font-family: inherit;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            user-select: none;
            white-space: nowrap;
        }
        .cat-filter-trigger:hover,
        .cat-filter-wrapper:hover .cat-filter-trigger {
            background: #fff;
            border-color: #6366f1;
            color: #4f46e5;
        }
        .cat-filter-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 9px;
            background: #6366f1;
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            transition: all 0.2s;
        }
        .cat-filter-badge.all-selected {
            background: #94a3b8;
        }
        .cat-filter-panel {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 260px;
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.14), 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.07);
            padding: 12px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px) scale(0.97);
            transform-origin: top left;
            transition: opacity 0.18s cubic-bezier(0.4,0,0.2,1),
                        visibility 0.18s cubic-bezier(0.4,0,0.2,1),
                        transform 0.18s cubic-bezier(0.4,0,0.2,1);
            z-index: 10000;
        }
        .cat-filter-wrapper:hover .cat-filter-panel,
        .cat-filter-panel:hover {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }
        .cat-filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
        }
        .cat-filter-title {
            font-size: 0.7rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .cat-select-all {
            font-size: 0.72rem;
            font-weight: 600;
            color: #6366f1;
            cursor: pointer;
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid rgba(99,102,241,0.25);
            background: rgba(99,102,241,0.06);
            transition: all 0.15s;
            user-select: none;
        }
        .cat-select-all:hover {
            background: rgba(99,102,241,0.15);
            border-color: #6366f1;
        }
        .cat-filter-items {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .cat-filter-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.12s;
            user-select: none;
        }
        .cat-filter-item:hover {
            background: #f8fafc;
        }
        .cat-filter-item input[type=checkbox] {
            display: none;
        }
        .cat-checkbox {
            width: 18px;
            height: 18px;
            border-radius: 5px;
            border: 2px solid #e2e8f0;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
            background: #fff;
        }
        .cat-filter-item.checked .cat-checkbox {
            border-color: var(--cat-color);
            background: var(--cat-color);
        }
        .cat-checkbox-check {
            opacity: 0;
            transition: opacity 0.12s;
        }
        .cat-filter-item.checked .cat-checkbox-check {
            opacity: 1;
        }
        .cat-filter-icon {
            width: 26px;
            height: 26px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: opacity 0.15s;
        }
        .cat-filter-item:not(.checked) .cat-filter-icon {
            opacity: 0.45;
        }
        .cat-filter-label {
            flex: 1;
            font-size: 0.8rem;
            font-weight: 500;
            color: #334155;
            transition: color 0.12s;
        }
        .cat-filter-item:not(.checked) .cat-filter-label {
            color: #94a3b8;
        }

        /* ===== SEARCH BAR ===== */
        .search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .search-input {
            padding: 8px 14px 8px 36px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(8px);
            font-size: 0.8rem;
            font-weight: 500;
            color: #334155;
            outline: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            font-family: inherit;
            width: 220px;
            transition: all 0.2s;
        }
        .search-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
            width: 260px;
        }
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }
        .search-suggestions {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 100%;
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.14), 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.07);
            max-height: 300px;
            overflow-y: auto;
            z-index: 10000;
            display: none;
        }
        .search-suggestions.active {
            display: block;
        }
        .search-suggestion-item {
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background 0.15s;
        }
        .search-suggestion-item:last-child {
            border-bottom: none;
        }
        .search-suggestion-item:hover {
            background: #f8fafc;
        }
        .search-suggestion-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            display: block;
        }
        .search-suggestion-cat {
            font-size: 0.65rem;
            color: #6366f1;
            font-weight: 600;
            margin-top: 2px;
            display: block;
        }
        .search-suggestion-empty {
            padding: 12px 14px;
            font-size: 0.8rem;
            color: #94a3b8;
            text-align: center;
        }

        /* Content transition */
        .panel-content {
            transition: opacity 0.2s ease;
        }
        .panel-content.fading {
            opacity: 0;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            #info-panel {
                top: auto;
                bottom: 0;
                left: 0;
                width: 100%;
                max-height: 55vh;
                border-radius: 20px 20px 0 0;
                box-shadow: 0 -4px 32px rgba(0,0,0,0.15);
            }
            #info-panel.panel-hidden {
                transform: translateY(110%);
            }
            .panel-header { border-radius: 20px 20px 0 0; }

            .filter-bar {
                left: 16px;
                top: auto;
                bottom: 16px;
                right: 16px;
            }
            .filter-bar select { flex: 1; }

            /* Mobile drag handle */
            .panel-header::after {
                content: '';
                display: block;
                width: 40px;
                height: 4px;
                background: rgba(255,255,255,0.3);
                border-radius: 2px;
                margin: 12px auto 0;
            }
        }
        </style>
        <title>WebGIS Fasilitas & Kependudukan</title>
    </head>
    <body>
        
        <!-- Peta (full-screen) -->
        <div id="map"></div>

        <!-- Info Panel (floating overlay) — inline styles as bulletproof fallback -->
        <div id="info-panel" style="position:fixed !important; top:16px !important; left:16px !important; width:320px; max-height:calc(100vh - 32px); z-index:9999 !important; background:rgba(255,255,255,0.97); border-radius:16px; box-shadow:0 8px 32px rgba(0,0,0,0.12); overflow-y:auto; overflow-x:hidden; border:1px solid rgba(0,0,0,0.06); pointer-events:auto; display:block !important; visibility:visible !important;">
            <div id="panel-content" class="panel-content">
                <!-- Content filled by JS -->
            </div>
        </div>

        <!-- Filter bar (floating) -->
        <div class="filter-bar" style="position:fixed !important; top:16px; left:352px; z-index:9999 !important; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            
            <!-- Search Bar -->
            <div class="search-wrapper">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="search-fasilitas" class="search-input" placeholder="Cari fasilitas..." autocomplete="off">
                <div class="search-suggestions" id="search-suggestions">
                    <!-- Suggestions via JS -->
                </div>
            </div>

            <select id="filter-wilayah" style="padding:8px 32px 8px 12px; border:1px solid rgba(0,0,0,0.1); border-radius:10px; background:rgba(255,255,255,0.95); font-size:0.8rem; font-weight:500; color:#334155; cursor:pointer; appearance:none; outline:none; box-shadow:0 2px 8px rgba(0,0,0,0.08); font-family:inherit;">
                <option value="all">Semua Kecamatan</option>
            </select>

            <!-- Category Filter (Hover Dropdown with Checkboxes) -->
            <div class="cat-filter-wrapper" id="cat-filter-wrapper">
                <div class="cat-filter-trigger" id="cat-filter-trigger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 4h12v2.172a2 2 0 0 1 -.586 1.414l-3.914 3.914v8.5l-4 -3v-5.5l-3.914 -3.914a2 2 0 0 1 -.586 -1.414v-2.172z"/></svg>
                    Filter Kategori
                    <span class="cat-filter-badge all-selected" id="cat-filter-badge">10</span>
                </div>
                <div class="cat-filter-panel" id="cat-filter-panel">
                    <div class="cat-filter-header">
                        <span class="cat-filter-title">Tampilkan Kategori</span>
                        <span class="cat-select-all" id="cat-select-all-btn" onclick="toggleAllCategories()">Pilih Semua</span>
                    </div>
                    <div class="cat-filter-items" id="cat-filter-items">
                        <!-- Filled by JS -->
                    </div>
                </div>
            </div>

            <button class="filter-btn" id="btn-show-route" onclick="toggleRoutePanel()" style="padding:8px 14px; border:1px solid rgba(0,0,0,0.1); border-radius:10px; background:rgba(255,255,255,0.95); font-size:0.8rem; font-weight:500; color:#3b82f6; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.08); font-family:inherit; display:flex; align-items:center; gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 16 16 12 12 8"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                Navigasi Rute
            </button>
            <button class="filter-btn" id="btn-reset-all" onclick="resetAllFilters()" style="padding:8px 14px; border:1px solid rgba(0,0,0,0.1); border-radius:10px; background:rgba(255,255,255,0.95); font-size:0.8rem; font-weight:500; color:#334155; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.08); font-family:inherit; display:flex; align-items:center; gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                Reset
            </button>
            <a href="/admin" class="filter-btn" style="text-decoration:none; padding:8px 14px; border:1px solid rgba(0,0,0,0.1); border-radius:10px; background:#4f46e5; font-size:0.8rem; font-weight:600; color:#ffffff !important; box-shadow:0 2px 8px rgba(99,102,241,0.3); font-family:inherit; display:flex; align-items:center; gap:6px; transition:all 0.2s;" onmouseover="this.style.background='#4338ca'; this.style.color='#ffffff'" onmouseout="this.style.background='#4f46e5'; this.style.color='#ffffff'">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/><path d="M20 12h-13l3 -3m0 6l-3 -3"/></svg>
                <span style="color:#ffffff !important;">Admin Panel</span>
            </a>
        </div>

        <!-- CUSTOM ROUTING PANEL -->
        <div id="custom-routing-panel" class="custom-routing-panel hidden">
            <div class="route-header">
                <h3>Cari Rute Perjalanan</h3>
                <button class="route-close" onclick="toggleRoutePanel()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="route-body">
                <div class="route-line"></div>
                <!-- Origin -->
                <div class="route-input-group">
                    <span class="route-icon origin"></span>
                    <input type="text" id="route-start" placeholder="Titik Awal (Ketik atau Lokasi Anda)..." autocomplete="off">
                    <div class="route-suggestions" id="route-start-suggestions"></div>
                </div>
                <!-- Destination -->
                <div class="route-input-group">
                    <span class="route-icon dest"></span>
                    <input type="text" id="route-end" placeholder="Titik Tujuan (Pilih Fasilitas)..." autocomplete="off">
                    <div class="route-suggestions" id="route-end-suggestions"></div>
                </div>
            </div>
            <div class="route-footer">
                <button class="route-btn primary" onclick="calculateRoute()">Tampilkan Rute</button>
                <button class="route-btn danger" id="btn-reset-route" onclick="resetMapRoute()" style="display:none;">Hapus</button>
            </div>
        </div>

        <!-- Scripts Dasar -->
        <script src="js/qgis2web_expressions.js"></script>
        <script src="js/leaflet.js"></script>
        <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
        
        <!-- Load Semua Data GeoJSON -->
        <script src="data/ADMINISTRASI_LN_50K_2.js"></script>
        <script src="data/ADMINISTRASIKECAMATAN_AR_50K_3.js"></script>
        <script src="data/JALAN_LN_50K_4.js"></script>
        
        <!-- Script Routing -->
        <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>

        <script>
        // ============================================================
        //  TABLER ICONS — SVG paths (inline, no CDN dependency)
        // ============================================================
        const TABLER_ICONS = {
            'school': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M22 9l-10 -4l-10 4l10 4l10 -4v6"/><path d="M6 10.6v5.4a6 3 0 0 0 12 0v-5.4"/>',
            'building-mosque': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21h7v-2a2 2 0 1 1 4 0v2h7"/><path d="M4 21v-10"/><path d="M20 21v-10"/><path d="M4 11h16"/><path d="M12 4.5c-3 0 -6 2.5 -6 6.5h12c0 -4 -3 -6.5 -6 -6.5z"/><path d="M12 2v2.5"/>',
            'building-hospital': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16"/><path d="M9 21v-4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v4"/><path d="M10 9h4"/><path d="M12 7v4"/>',
            'gas-station': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 11h1a2 2 0 0 1 2 2v3a1.5 1.5 0 0 0 3 0v-7l-3 -3"/><path d="M4 20v-14a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v14"/><path d="M3 20h12"/><path d="M18 7v1a1 1 0 0 0 1 1"/><path d="M4 11h10"/>',
            'trophy': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 21l8 0"/><path d="M12 17l0 4"/><path d="M7 4l10 0"/><path d="M17 4v8a5 5 0 0 1 -10 0v-8"/><path d="M5 9m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M19 9m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>',
            'stethoscope': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h-1a2 2 0 0 0 -2 2v3.5h0a5.5 5.5 0 0 0 11 0v-3.5a2 2 0 0 0 -2 -2h-1"/><path d="M8 15a6 6 0 1 0 12 0v-3"/><path d="M11 3v2"/><path d="M6 3v2"/><path d="M20 10m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>',
            'baby-carriage': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M18 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M2 5h2.5l1.632 4.897a6 6 0 0 0 5.693 4.103h2.675a5.5 5.5 0 0 0 0 -11h-6"/>',
            'abc': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 16v-6a2 2 0 1 1 4 0v6"/><path d="M3 13h4"/><path d="M10 8v6a2 2 0 1 0 4 0v-1a2 2 0 1 0 -4 0"/><path d="M20 10a2 2 0 0 0 -2 -2h0a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h0a2 2 0 0 0 2 -2"/>',
            'cross': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 4h4v4h4v4h-4v8h-4v-8h-4v-4h4z"/>',
            'mountain': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 20h18l-6.921 -14.612a2.3 2.3 0 0 0 -4.158 0l-6.921 14.612z"/><path d="M7.5 11l2 2.5l2 -2.5"/>',
            'map-2': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 18.5l-3 -1.5l-6 3v-13l6 -3l6 3l6 -3v7.5"/><path d="M9 4v13"/><path d="M15 7v5.5"/><path d="M21.121 20.121a3 3 0 1 0 -4.242 0c.418 .419 1.125 1.045 2.121 1.879c1.051 -.89 1.759 -1.516 2.121 -1.879z"/><path d="M19 18v.01"/>',
            'map-pin': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z"/>',
            'cursor-default': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 3l14 8.5l-6.5 2l-3 5.5z"/>',
            'info-circle': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M12 9h.01"/><path d="M11 12h1v4h1"/>',
            'login': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/><path d="M20 12h-13l3 -3m0 6l-3 -3"/>',
        };

        function tablerSvg(name, size, color) {
            size = size || 16;
            color = color || 'currentColor';
            return '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + size + '" viewBox="0 0 24 24" fill="none" stroke="' + color + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + (TABLER_ICONS[name] || '') + '</svg>';
        }

        // ============================================================
        //  KATEGORI CONFIG — 10 categories with icon & color
        // ============================================================
        const KATEGORI_CONFIG = [
            { key: 'pendidikan_count',   label: 'Pendidikan',     icon: 'school',            color: '#3B82F6' },
            { key: 'ibadah_count',       label: 'Sarana Ibadah',  icon: 'building-mosque',   color: '#8B5CF6' },
            { key: 'rumah_sakit_count',  label: 'Rumah Sakit',    icon: 'building-hospital', color: '#EF4444' },
            { key: 'spbu_count',         label: 'SPBU',           icon: 'gas-station',       color: '#F59E0B' },
            { key: 'olahraga_count',     label: 'Arena Olahraga', icon: 'trophy',            color: '#10B981' },
            { key: 'klinik_count',       label: 'Klinik',         icon: 'stethoscope',       color: '#06B6D4' },
            { key: 'posyandu_count',     label: 'Posyandu',       icon: 'baby-carriage',     color: '#EC4899' },
            { key: 'tk_paud_count',      label: 'TK / PAUD',     icon: 'abc',               color: '#F97316' },
            { key: 'kuburan_count',      label: 'Kuburan Umum',   icon: 'cross',             color: '#6B7280' },
            { key: 'wisata_count',       label: 'Tempat Wisata',  icon: 'mountain',          color: '#84CC16' },
        ];

        // ============================================================
        //  PANEL STATE MANAGEMENT
        // ============================================================
        let panelManuallyClosed = false;
        let kecamatanData = {};
        let currentHoveredKecamatan = null;

        // ============================================================
        //  RENDER: Default Panel (City Overview)
        // ============================================================
        function renderDefaultPanel() {
            const panel = document.getElementById('panel-content');
            
            // Compute city totals from kecamatanData
            let totalFasilitas = 0;
            let totalKecamatan = Object.keys(kecamatanData).length;
            let cityCategories = {};
            KATEGORI_CONFIG.forEach(k => { cityCategories[k.key] = 0; });

            Object.values(kecamatanData).forEach(kec => {
                KATEGORI_CONFIG.forEach(k => {
                    const val = kec[k.key] || 0;
                    cityCategories[k.key] += val;
                    totalFasilitas += val;
                });
            });

            // Sort categories descending for city view
            const sorted = [...KATEGORI_CONFIG].sort((a, b) => (cityCategories[b.key] || 0) - (cityCategories[a.key] || 0));
            const top2 = sorted.slice(0, 2);

            let html = '';

            // Header
            html += '<div class="panel-header">';
            html += '<button class="panel-close-btn" onclick="closePanel()" title="Tutup panel">';
            html += '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
            html += '</button>';
            html += '<div class="panel-header-title">' + tablerSvg('map-2', 18, '#a5b4fc') + ' Kota Bandar Lampung</div>';
            html += '<div class="panel-header-sub">Peta Sebaran Fasilitas Publik</div>';
            html += '</div>';

            // Body
            html += '<div class="panel-body">';

            // Hero stat
            html += '<div class="default-hero">';
            html += '<div class="default-hero-number">' + totalFasilitas.toLocaleString('id-ID') + '</div>';
            html += '<div class="default-hero-sub">Fasilitas terdata di ' + totalKecamatan + ' Kecamatan</div>';
            html += '</div>';

            html += '<div class="panel-divider"></div>';

            // Top categories as stat cards
            html += '<div class="section-label">Kategori Terbanyak</div>';
            html += '<div class="stat-cards">';
            html += '<div class="stat-card primary">';
            html += '<div class="stat-card-value">' + totalFasilitas.toLocaleString('id-ID') + '</div>';
            html += '<div class="stat-card-label">Total</div>';
            html += '</div>';
            top2.forEach(k => {
                html += '<div class="stat-card">';
                html += '<div class="stat-card-value" style="color:' + k.color + '">' + (cityCategories[k.key] || 0) + '</div>';
                html += '<div class="stat-card-label">' + k.label + '</div>';
                html += '</div>';
            });
            html += '</div>';

            // All categories with bars
            html += '<div class="section-label">Semua Kategori</div>';
            html += '<div class="kategori-list">';
            sorted.forEach(k => {
                const count = cityCategories[k.key] || 0;
                const pct = totalFasilitas > 0 ? Math.round(count / totalFasilitas * 100) : 0;
                const isZero = count === 0;
                html += '<div class="kategori-item' + (isZero ? ' zero-count' : '') + '">';
                html += '<div class="kategori-row">';
                html += '<div class="kategori-icon" style="background:' + k.color + '15; color:' + k.color + '">' + tablerSvg(k.icon, 16, k.color) + '</div>';
                html += '<span class="kategori-label">' + k.label + '</span>';
                html += '<span class="kategori-count">' + count + '</span>';
                html += '</div>';
                html += '<div class="kategori-bar"><div class="kategori-bar-fill" style="width:' + pct + '%;background:' + k.color + '"></div></div>';
                html += '</div>';
            });
            html += '</div>';

            // Hint
            html += '<div class="hint-box">';
            html += tablerSvg('cursor-default', 16, '#0ea5e9');
            html += '<p>Arahkan kursor ke area kecamatan pada peta untuk melihat detail fasilitas per wilayah.</p>';
            html += '</div>';

            html += '</div>'; // panel-body
            panel.innerHTML = html;
        }

        // ============================================================
        //  RENDER: Kecamatan Sidebar (Hover/Click state)
        // ============================================================
        function renderKecamatanPanel(data) {
            const panel = document.getElementById('panel-content');

            // Compute total from the 10 keys
            let total = 0;
            KATEGORI_CONFIG.forEach(k => { total += (data[k.key] || 0); });

            // Sort descending
            const sorted = [...KATEGORI_CONFIG].sort((a, b) => (data[b.key] || 0) - (data[a.key] || 0));
            const top2 = sorted.filter(k => (data[k.key] || 0) > 0).slice(0, 2);

            // Format demografi
            const populasi = data.populasi ? data.populasi.toLocaleString('id-ID') + ' jiwa' : '-';
            const luas = data.luas_wilayah ? data.luas_wilayah.toFixed(2) + ' km\u00B2' : '-';
            const kepadatan = data.kepadatan ? data.kepadatan.toLocaleString('id-ID') + ' /km\u00B2' : '-';

            let html = '';

            // Header
            html += '<div class="panel-header">';
            html += '<button class="panel-close-btn" onclick="closePanel()" title="Tutup panel">';
            html += '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
            html += '</button>';
            html += '<div class="panel-header-title">' + tablerSvg('map-pin', 18, '#a5b4fc') + ' ' + (data.nama || 'Kecamatan') + '</div>';
            html += '<div class="panel-header-sub">Kota Bandar Lampung</div>';
            html += '</div>';

            // Body
            html += '<div class="panel-body">';

            // Stat cards row
            html += '<div class="stat-cards">';
            html += '<div class="stat-card primary">';
            html += '<div class="stat-card-value">' + total + '</div>';
            html += '<div class="stat-card-label">Total</div>';
            html += '</div>';
            if (top2.length >= 1) {
                html += '<div class="stat-card">';
                html += '<div class="stat-card-value" style="color:' + top2[0].color + '">' + (data[top2[0].key] || 0) + '</div>';
                html += '<div class="stat-card-label">' + top2[0].label + '</div>';
                html += '</div>';
            }
            if (top2.length >= 2) {
                html += '<div class="stat-card">';
                html += '<div class="stat-card-value" style="color:' + top2[1].color + '">' + (data[top2[1].key] || 0) + '</div>';
                html += '<div class="stat-card-label">' + top2[1].label + '</div>';
                html += '</div>';
            }
            // If less than 2 top categories, fill with empty stat card
            for (let i = top2.length; i < 2; i++) {
                html += '<div class="stat-card">';
                html += '<div class="stat-card-value" style="color:#94a3b8">0</div>';
                html += '<div class="stat-card-label">—</div>';
                html += '</div>';
            }
            html += '</div>';

            // Demografi
            html += '<div class="section-label">Demografi</div>';
            html += '<div class="demografi-grid">';
            html += '<div class="demografi-card"><div class="demografi-label">Populasi</div><div class="demografi-value">' + populasi + '</div></div>';
            html += '<div class="demografi-card"><div class="demografi-label">Luas Wilayah</div><div class="demografi-value">' + luas + '</div></div>';
            html += '<div class="demografi-card"><div class="demografi-label">Kepadatan</div><div class="demografi-value">' + kepadatan + '</div></div>';
            html += '<div class="demografi-card"><div class="demografi-label">Fasilitas</div><div class="demografi-value">' + total + ' unit</div></div>';
            html += '</div>';

            html += '<div class="panel-divider"></div>';

            // Category list
            html += '<div class="section-label">Sebaran Per Kategori</div>';
            html += '<div class="kategori-list">';
            sorted.forEach(k => {
                const count = data[k.key] || 0;
                const pct = total > 0 ? Math.round(count / total * 100) : 0;
                const isZero = count === 0;
                html += '<div class="kategori-item' + (isZero ? ' zero-count' : '') + '">';
                html += '<div class="kategori-row">';
                html += '<div class="kategori-icon" style="background:' + k.color + '15; color:' + k.color + '">' + tablerSvg(k.icon, 16, k.color) + '</div>';
                html += '<span class="kategori-label">' + k.label + '</span>';
                html += '<span class="kategori-count">' + count + '</span>';
                html += '</div>';
                html += '<div class="kategori-bar"><div class="kategori-bar-fill" style="width:' + pct + '%;background:' + k.color + '"></div></div>';
                html += '</div>';
            });
            html += '</div>';

            html += '</div>'; // panel-body
            panel.innerHTML = html;
        }

        // ============================================================
        //  PANEL CONTROLS
        // ============================================================
        function showPanel() {
            document.getElementById('info-panel').classList.remove('panel-hidden');
        }

        function closePanel() {
            panelManuallyClosed = true;
            document.getElementById('info-panel').classList.add('panel-hidden');
        }

        function transitionPanel(renderFn) {
            const content = document.getElementById('panel-content');
            content.classList.add('fading');
            setTimeout(() => {
                renderFn();
                content.classList.remove('fading');
            }, 200);
        }

        // ============================================================
        //  API & MAP INITIALIZATION
        // ============================================================
        async function loadKecamatanData() {
            try {
                const response = await fetch('/api/kecamatan');
                if (!response.ok) {
                    throw new Error('API returned ' + response.status + ': ' + response.statusText);
                }
                kecamatanData = await response.json();
                console.log('✓ Kecamatan data loaded:', Object.keys(kecamatanData).length, 'kecamatan');
            } catch (error) {
                console.error('✗ Failed to load kecamatan data:', error);
            }
        }

        document.addEventListener('DOMContentLoaded', async () => {
            await loadKecamatanData();
            renderDefaultPanel();
            showPanel();
            initMap();
        });

        function initMap() {
        // === INISIALISASI PETA ===
        var map = L.map('map', {
            zoomControl: true, maxZoom: 28, minZoom: 1
        }).fitBounds([[-5.4809,105.1770],[-5.3378,105.3711]]);
        
        var baseMaps = {
            "Google Hybrid": L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', { maxZoom: 28 }).addTo(map)
        };

        // === ICON CUSTOM ===
        var iconRumahSakit = L.icon({ iconUrl: 'images/hospital.png', iconSize: [30, 30], iconAnchor: [15, 30] });
        var iconPendidikan = L.icon({ iconUrl: 'images/school.png', iconSize: [30, 30], iconAnchor: [15, 30] });
        var iconSPBU = L.icon({ iconUrl: 'images/gas.png', iconSize: [30, 30], iconAnchor: [15, 30] });
        var iconIbadah = L.icon({ iconUrl: 'images/mosque.png', iconSize: [30, 30], iconAnchor: [15, 30] });
        var iconOlahraga = L.icon({ iconUrl: 'images/sport.png', iconSize: [30, 30], iconAnchor: [15, 30] });

        // === ICON KATEGORI BARU ===
        var iconKlinik = L.icon({ iconUrl: 'images/clinic.png', iconSize: [30, 30], iconAnchor: [15, 30] });
        var iconPosyandu = L.icon({ iconUrl: 'images/posyandu.png', iconSize: [30, 30], iconAnchor: [15, 30] });
        var iconKindergarten = L.icon({ iconUrl: 'images/kindergarten.png', iconSize: [30, 30], iconAnchor: [15, 30] });
        var iconCemetery = L.icon({ iconUrl: 'images/cemetery.png', iconSize: [30, 30], iconAnchor: [15, 30] });
        var iconTourism = L.icon({ iconUrl: 'images/tourism.png', iconSize: [30, 30], iconAnchor: [15, 30] });

        var iconUnila = L.icon({ iconUrl: 'images/school.png', iconSize: [35, 35], iconAnchor: [17, 35] }); 

        var currentRouteIcon = null; 
        var activeLayersBeforeRouting = [];
        var routeStartLatLng = null;
        var routeEndLatLng = null;

        // === ROUTING CONTROL ===
        var routingControl = L.Routing.control({
            waypoints: [],
            routeWhileDragging: true,
            addWaypoints: true,
            fitSelectedRoutes: true,
            showAlternatives: false,
            geocoder: L.Control.Geocoder.nominatim(),
            lineOptions: { styles: [{color: '#6366f1', opacity: 0.9, weight: 6}] },
            createMarker: function(i, waypoint, n) {
                let icon;
                if (i === 0) icon = L.icon({ iconUrl: 'images/marker-icon.png', iconSize: [25, 41], iconAnchor: [12, 41] });
                else if (i === n - 1 && currentRouteIcon) icon = currentRouteIcon; 
                else icon = L.icon({ iconUrl: 'images/marker-icon.png', iconSize: [25, 41], iconAnchor: [12, 41], className: 'hue-rotate' }); 
                
                return L.marker(waypoint.latLng, { icon: icon });
            }
        }).addTo(map);

        // === REAL DATA MATCHER ===
        function getKecamatanInfo(namaKecamatan) {
            if (kecamatanData[namaKecamatan]) {
                return kecamatanData[namaKecamatan];
            }
            // Case-insensitive match
            const key = Object.keys(kecamatanData).find(
                k => k.toLowerCase() === namaKecamatan.toLowerCase()
            );
            if (key) return kecamatanData[key];
            // Fallback
            return {
                nama: namaKecamatan,
                populasi: null,
                luas_wilayah: null,
                kepadatan: null,
                total_fasilitas: 0
            };
        }

        // === HIGHLIGHT AREA KECAMATAN ===
        var highlightLayer;
        function highlightFeature(e) {
            highlightLayer = e.target;
            highlightLayer.setStyle({ fillColor: '#6366f1', fillOpacity: 0.2, weight: 3, color: '#4f46e5' });

            var props = highlightLayer.feature.properties;
            var namaWilayah = props.NAMOBJ || 'Wilayah Tidak Diketahui';
            currentHoveredKecamatan = namaWilayah;

            // Only update panel on hover if not manually closed
            if (!panelManuallyClosed) {
                const info = getKecamatanInfo(namaWilayah);
                showPanel();
                transitionPanel(() => renderKecamatanPanel(info));
            }
        }

        function resetHighlight(e) {
            layer_ADMINISTRASIKECAMATAN_AR_50K_3.resetStyle(e.target);
            currentHoveredKecamatan = null;

            // Return to default panel on mouseout if not manually closed
            if (!panelManuallyClosed) {
                transitionPanel(() => renderDefaultPanel());
            }
        }

        function clickFeature(e) {
            var props = e.target.feature.properties;
            var namaWilayah = props.NAMOBJ || 'Wilayah Tidak Diketahui';
            const info = getKecamatanInfo(namaWilayah);

            // Clicking always opens panel (even if manually closed)
            panelManuallyClosed = false;
            showPanel();
            transitionPanel(() => renderKecamatanPanel(info));
        }

        // === LAYERS DASAR (AREA & JALAN) ===
        var layer_ADMINISTRASIKECAMATAN_AR_50K_3 = new L.geoJson(json_ADMINISTRASIKECAMATAN_AR_50K_3, {
            style: function() { return { color: '#6366f1', weight: 2, fillOpacity: 0.04, fillColor: '#6366f1' } },
            onEachFeature: function(feature, layer) {
                layer.on({
                    mouseover: highlightFeature,
                    mouseout: resetHighlight,
                    click: clickFeature
                });
            }
        }).addTo(map);

        var layer_JALAN_LN_50K_4 = new L.geoJson(json_JALAN_LN_50K_4, {
            style: function() { return { color: '#ecc94b', weight: 1.5, opacity: 0.85 } }
        }).addTo(map);

        // 1. Ambil Nama Kecamatan & Isi Dropdown
        var dropdown = document.getElementById('filter-wilayah');
        var semuaKecamatan = json_ADMINISTRASIKECAMATAN_AR_50K_3.features.map(f => f.properties.NAMOBJ).filter(Boolean);
        var kecamatanUnik = [...new Set(semuaKecamatan)].sort();
        
        kecamatanUnik.forEach(nama => {
            let opt = document.createElement('option');
            opt.value = nama; opt.innerHTML = nama;
            dropdown.appendChild(opt);
        });

        // 2. Fungsi Eksekusi Filter
        dropdown.addEventListener('change', function(e) {
            filterMapByKecamatan(e.target.value);
        });

        function filterMapByKecamatan(namaKecamatan) {
            resetMapRoute();

            if (namaKecamatan === 'all') {
                // Apply category filter on top of showing all kecamatan
                applyCurrentFilters();
                map.fitBounds(layer_ADMINISTRASIKECAMATAN_AR_50K_3.getBounds());
                // Show default panel
                panelManuallyClosed = false;
                showPanel();
                transitionPanel(() => renderDefaultPanel());
                return;
            }

            var polyKecamatan = json_ADMINISTRASIKECAMATAN_AR_50K_3.features.find(f => f.properties.NAMOBJ === namaKecamatan);
            if (polyKecamatan) {
                var bounds = L.geoJson(polyKecamatan).getBounds();
                map.fitBounds(bounds);
            }

            // Apply both kecamatan + category filter
            applyCurrentFilters();

            // Show kecamatan panel
            const info = getKecamatanInfo(namaKecamatan);
            panelManuallyClosed = false;
            showPanel();
            transitionPanel(() => renderKecamatanPanel(info));
        }

        // Tombol Reset Filter Utama
        window.resetAllFilters = function() {
            document.getElementById('filter-wilayah').value = 'all';
            // Reset kategori ke semua dipilih
            ALL_KATEGORI_LABELS.forEach(k => { activeCategoryFilters[k] = true; });
            updateCategoryFilterUI();
            filterMapByKecamatan('all');
        }


        // === LOGIKA CUSTOM ROUTING UI ===
        window.toggleRoutePanel = function() {
            var panel = document.getElementById('custom-routing-panel');
            panel.classList.toggle('hidden');
        }

        // Suggestion for START (Origin) using Nominatim
        var inputStart = document.getElementById('route-start');
        var suggStart = document.getElementById('route-start-suggestions');
        let geocodeTimeout;

        inputStart.addEventListener('input', function() {
            clearTimeout(geocodeTimeout);
            var val = this.value.toLowerCase().trim();
            suggStart.innerHTML = '';
            
            if (val.length < 3) {
                suggStart.classList.remove('active');
                return;
            }

            // Always add "Gunakan Lokasi Saat Ini" as first option
            var locItem = document.createElement('div');
            locItem.className = 'route-suggestion-item';
            locItem.innerHTML = '<strong>📍 Gunakan Lokasi Saat Ini (GPS)</strong>';
            locItem.addEventListener('click', function() {
                if ("geolocation" in navigator) {
                    inputStart.value = "Mencari lokasi...";
                    suggStart.classList.remove('active');
                    navigator.geolocation.getCurrentPosition(function(position) {
                        routeStartLatLng = L.latLng(position.coords.latitude, position.coords.longitude);
                        inputStart.value = "📍 Lokasi Saya";
                    }, function(err) {
                        inputStart.value = "";
                        let errorMsg = "Gagal mendapatkan lokasi. ";
                        if (err.code === 1) errorMsg += "Izin akses lokasi ditolak oleh browser Anda. Mohon izinkan akses lokasi (GPS) pada pengaturan browser.";
                        else if (err.code === 2) errorMsg += "Sinyal GPS / lokasi tidak tersedia di perangkat ini. Pastikan fitur Location Windows/HP Anda menyala.";
                        else if (err.code === 3) errorMsg += "Waktu permintaan lokasi habis (timeout). Sinyal GPS mungkin lemah.";
                        alert(errorMsg);
                    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
                }
            });
            suggStart.appendChild(locItem);

            // Fetch from Nominatim (bounded to Bandar Lampung roughly)
            geocodeTimeout = setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(val)}&viewbox=105.15,-5.52,105.40,-5.25&bounded=1&limit=5`)
                .then(res => res.json())
                .then(data => {
                    data.forEach(place => {
                        var item = document.createElement('div');
                        item.className = 'route-suggestion-item';
                        item.textContent = place.display_name;
                        item.addEventListener('click', function() {
                            routeStartLatLng = L.latLng(place.lat, place.lon);
                            inputStart.value = place.display_name.split(',')[0]; // simplify name
                            suggStart.classList.remove('active');
                        });
                        suggStart.appendChild(item);
                    });
                    suggStart.classList.add('active');
                });
            }, 500);
        });

        // Suggestion for END (Destination) using Local GeoJSON
        var inputEnd = document.getElementById('route-end');
        var suggEnd = document.getElementById('route-end-suggestions');

        inputEnd.addEventListener('input', function() {
            var val = this.value.toLowerCase().trim();
            suggEnd.innerHTML = '';
            
            if (val.length < 2) {
                suggEnd.classList.remove('active');
                return;
            }

            if (!globalDbGeojson) return;
            var matches = globalDbGeojson.features.filter(function(f) {
                var name = (f.properties.nama || '').toLowerCase();
                return name.includes(val);
            }).slice(0, 10);

            if (matches.length > 0) {
                matches.forEach(function(f) {
                    var item = document.createElement('div');
                    item.className = 'route-suggestion-item';
                    item.innerHTML = '<strong>' + (f.properties.nama || 'Tanpa Nama') + '</strong><br><span style="font-size:0.7rem;color:#64748b;">' + (f.properties.kategori || 'Lainnya') + '</span>';
                    
                    item.addEventListener('click', function() {
                        var lat = f.geometry.coordinates[1];
                        var lng = f.geometry.coordinates[0];
                        routeEndLatLng = L.latLng(lat, lng);
                        currentRouteIcon = kategoriIconMap[f.properties.kategori] || iconOlahraga;
                        
                        inputEnd.value = f.properties.nama;
                        suggEnd.classList.remove('active');
                    });
                    suggEnd.appendChild(item);
                });
                suggEnd.classList.add('active');
            }
        });

        // Hide suggestions on outside click
        document.addEventListener('click', function(e) {
            if (!inputStart.contains(e.target) && !suggStart.contains(e.target)) suggStart.classList.remove('active');
            if (!inputEnd.contains(e.target) && !suggEnd.contains(e.target)) suggEnd.classList.remove('active');
        });

        window.calculateRoute = function() {
            if (!routeStartLatLng) {
                alert("Silakan pilih Titik Awal terlebih dahulu.");
                return;
            }
            if (!routeEndLatLng) {
                alert("Silakan pilih Titik Tujuan terlebih dahulu.");
                return;
            }

            activeLayersBeforeRouting = pointLayers.filter(layer => map.hasLayer(layer));
            pointLayers.forEach(layer => { if(map.hasLayer(layer)) map.removeLayer(layer); });
            
            routingControl.setWaypoints([routeStartLatLng, routeEndLatLng]);
            document.getElementById('btn-reset-route').style.display = 'block';
        }

        window.resetMapRoute = function() {
            routingControl.setWaypoints([]); 
            activeLayersBeforeRouting.forEach(layer => { map.addLayer(layer); });
            document.getElementById('btn-reset-route').style.display = 'none';
            routeStartLatLng = null;
            routeEndLatLng = null;
            inputStart.value = '';
            inputEnd.value = '';
        }

        function startRouteMode(latlng, iconType) {
            // Open panel automatically
            document.getElementById('custom-routing-panel').classList.remove('hidden');
            
            // Set destination
            routeEndLatLng = latlng;
            currentRouteIcon = iconType;
            
            // Find name of destination
            let destName = "Lokasi Pilihan";
            if (globalDbGeojson) {
                var feature = globalDbGeojson.features.find(f => 
                    f.geometry.coordinates[1] === latlng.lat && f.geometry.coordinates[0] === latlng.lng
                );
                if (feature) destName = feature.properties.nama;
            }
            inputEnd.value = destName;

            // Auto GPS for start if empty
            if (!routeStartLatLng && "geolocation" in navigator) {
                inputStart.value = "Mencari lokasi saya...";
                navigator.geolocation.getCurrentPosition(function(position) {
                    routeStartLatLng = L.latLng(position.coords.latitude, position.coords.longitude);
                    inputStart.value = "📍 Lokasi Saya";
                    calculateRoute();
                }, function(err) {
                    inputStart.value = "";
                    inputStart.placeholder = "Ketik Titik Awal (GPS diblokir/mati)";
                    let errorMsg = "Gagal otomatis mendeteksi lokasi GPS. ";
                    if (err.code === 1) errorMsg += "Browser menolak akses. Mohon ketik manual lokasi awal Anda.";
                    else if (err.code === 2) errorMsg += "Lokasi OS/Device mati. Mohon ketik manual lokasi awal Anda.";
                    else if (err.code === 3) errorMsg += "Sinyal GPS timeout. Mohon ketik manual lokasi awal Anda.";
                    console.warn(errorMsg);
                }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
            } else if (routeStartLatLng) {
                calculateRoute();
            }
        }


        // === MENU LAYER CONTROL ===
        var dbFasilitasLayer = L.layerGroup().addTo(map);
        var pointLayers = [dbFasilitasLayer];

        var overlayMaps = {
            "Jaringan Jalan": layer_JALAN_LN_50K_4,
            "Batas Kecamatan": layer_ADMINISTRASIKECAMATAN_AR_50K_3,
            "Semua Fasilitas": dbFasilitasLayer
        };

        L.control.layers(baseMaps, overlayMaps, {
            collapsed: true,
            position: 'topright'
        }).addTo(map);

        // === LOAD FASILITAS DARI DATABASE ===
        var globalDbGeojson = null;
        
        var kategoriIconMap = {
            'Rumah Sakit': iconRumahSakit,
            'Pendidikan': iconPendidikan,
            'SPBU': iconSPBU,
            'Sarana Ibadah': iconIbadah,
            'Arena Olahraga': iconOlahraga,
            'Klinik': iconKlinik,
            'Posyandu': iconPosyandu,
            'TK / PAUD': iconKindergarten,
            'Kuburan Umum': iconCemetery,
            'Tempat Wisata': iconTourism
        };

        // === CATEGORY FILTER STATE ===
        // Map label → true/false (checked state), initialized to all true
        var activeCategoryFilters = {};
        var ALL_KATEGORI_LABELS = Object.keys(kategoriIconMap);
        ALL_KATEGORI_LABELS.forEach(k => { activeCategoryFilters[k] = true; });

        // Initialize category filter checkboxes in the DOM
        function initCategoryFilter() {
            var container = document.getElementById('cat-filter-items');
            if (!container) return;
            container.innerHTML = '';

            // Map label → KATEGORI_CONFIG entry for icon/color
            var labelToConfig = {};
            KATEGORI_CONFIG.forEach(cfg => { labelToConfig[cfg.label] = cfg; });

            ALL_KATEGORI_LABELS.forEach(function(label) {
                var cfg = labelToConfig[label] || { color: '#94a3b8', icon: 'map-pin' };
                var checked = activeCategoryFilters[label];

                var item = document.createElement('label');
                item.className = 'cat-filter-item' + (checked ? ' checked' : '');
                item.style.setProperty('--cat-color', cfg.color);
                item.setAttribute('data-label', label);
                item.innerHTML = [
                    '<input type="checkbox" ' + (checked ? 'checked' : '') + ' data-label="' + label + '">',
                    '<span class="cat-checkbox">',
                        '<svg class="cat-checkbox-check" xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
                    '</span>',
                    '<span class="cat-filter-icon" style="background:' + cfg.color + '18;">',
                        tablerSvg(cfg.icon, 15, cfg.color),
                    '</span>',
                    '<span class="cat-filter-label">' + label + '</span>'
                ].join('');

                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    activeCategoryFilters[label] = !activeCategoryFilters[label];
                    updateCategoryFilterUI();
                    applyCurrentFilters();
                });

                container.appendChild(item);
            });

            updateCategoryFilterBadge();
        }

        function updateCategoryFilterUI() {
            var items = document.querySelectorAll('.cat-filter-item');
            items.forEach(function(item) {
                var label = item.getAttribute('data-label');
                if (activeCategoryFilters[label]) {
                    item.classList.add('checked');
                } else {
                    item.classList.remove('checked');
                }
            });
            updateCategoryFilterBadge();
        }

        function updateCategoryFilterBadge() {
            var badge = document.getElementById('cat-filter-badge');
            var btn = document.getElementById('cat-select-all-btn');
            if (!badge) return;
            var checkedCount = ALL_KATEGORI_LABELS.filter(k => activeCategoryFilters[k]).length;
            var total = ALL_KATEGORI_LABELS.length;
            badge.textContent = checkedCount === total ? 'Semua' : checkedCount + '/' + total;
            if (checkedCount === total) {
                badge.classList.add('all-selected');
                if (btn) btn.textContent = 'Hapus Semua';
            } else {
                badge.classList.remove('all-selected');
                if (btn) btn.textContent = checkedCount === 0 ? 'Pilih Semua' : 'Pilih Semua';
            }
        }

        window.toggleAllCategories = function() {
            var checkedCount = ALL_KATEGORI_LABELS.filter(k => activeCategoryFilters[k]).length;
            var selectAll = (checkedCount < ALL_KATEGORI_LABELS.length);
            ALL_KATEGORI_LABELS.forEach(k => { activeCategoryFilters[k] = selectAll; });
            updateCategoryFilterUI();
            applyCurrentFilters();
        };

        // Apply both kecamatan filter AND category filter
        function applyCurrentFilters() {
            if (!globalDbGeojson) return;
            var wilayah = document.getElementById('filter-wilayah').value;
            var features = globalDbGeojson.features;

            // 1. Filter by kecamatan
            if (wilayah !== 'all') {
                features = features.filter(f => f.properties.kecamatan === wilayah);
            }

            // 2. Filter by category
            features = features.filter(function(f) {
                var kat = f.properties.kategori || '';
                return activeCategoryFilters[kat] === true;
            });

            renderDbFasilitas({ type: 'FeatureCollection', features: features });
        }

        function renderDbFasilitas(geojson) {
            dbFasilitasLayer.clearLayers();
            L.geoJson(geojson, {
                pointToLayer: function(feature, latlng) {
                    var kategori = feature.properties.kategori || 'Lainnya';
                    var icon = kategoriIconMap[kategori] || iconOlahraga;
                    var marker = L.marker(latlng, { icon: icon });
                    
                    // Attach ID for search lookup
                    feature.properties._leaflet_id = marker._leaflet_id;
                    
                    marker.bindTooltip(feature.properties.nama || 'Fasilitas Baru', {
                        permanent: false, direction: 'top', offset: [0, -25]
                    });
                    
                    var popupContent = '<div style="min-width:180px;font-family:Inter,sans-serif;">' +
                        '<strong style="font-size:14px;">' + (feature.properties.nama || '') + '</strong><br>' +
                        '<span style="color:#6366f1;font-size:12px;font-weight:600;">' + kategori + '</span><br>';
                    if (feature.properties.kecamatan && feature.properties.kecamatan !== '-') {
                        popupContent += '<span style="color:#64748b;font-size:12px;">Kec. ' + feature.properties.kecamatan + '</span><br>';
                    }
                    if (feature.properties.deskripsi) {
                        popupContent += '<p style="font-size:12px;color:#475569;margin:6px 0 0;">' + feature.properties.deskripsi + '</p>';
                    }
                    if (feature.properties.foto) {
                        popupContent += '<img src="/storage/' + feature.properties.foto + '" style="width:100%;max-height:120px;object-fit:cover;margin-top:8px;border-radius:6px;" />';
                    }
                    popupContent += '</div>';
                    marker.bindPopup(popupContent);
                    
                    marker.on('click', function(e) { startRouteMode(e.latlng, icon); });
                    return marker;
                }
            }).addTo(dbFasilitasLayer);
        }

        // === SEARCH LOGIC ===
        var searchInput = document.getElementById('search-fasilitas');
        var suggestionsContainer = document.getElementById('search-suggestions');

        searchInput.addEventListener('input', function() {
            var val = this.value.toLowerCase().trim();
            suggestionsContainer.innerHTML = '';
            
            if (val.length < 2) {
                suggestionsContainer.classList.remove('active');
                return;
            }

            if (!globalDbGeojson) return;

            // Apply current filters to search pool
            var wilayah = document.getElementById('filter-wilayah').value;
            var pool = globalDbGeojson.features.filter(function(f) {
                var kat = f.properties.kategori || '';
                var matchKat = activeCategoryFilters[kat] === true;
                var matchWil = (wilayah === 'all' || f.properties.kecamatan === wilayah);
                return matchKat && matchWil;
            });

            var matches = pool.filter(function(f) {
                var name = (f.properties.nama || '').toLowerCase();
                return name.includes(val);
            });

            // Show max 10 suggestions
            var maxMatches = matches.slice(0, 10);

            if (maxMatches.length === 0) {
                suggestionsContainer.innerHTML = '<div class="search-suggestion-empty">Tidak ada hasil ditemukan</div>';
            } else {
                maxMatches.forEach(function(f) {
                    var item = document.createElement('div');
                    item.className = 'search-suggestion-item';
                    item.innerHTML = '<span class="search-suggestion-title">' + (f.properties.nama || 'Tanpa Nama') + '</span>' +
                                     '<span class="search-suggestion-cat">' + (f.properties.kategori || 'Lainnya') + ' • Kec. ' + (f.properties.kecamatan || '-') + '</span>';
                    
                    item.addEventListener('click', function() {
                        var lat = f.geometry.coordinates[1];
                        var lng = f.geometry.coordinates[0];
                        var latlng = L.latLng(lat, lng);
                        
                        map.setView(latlng, 17, { animate: true, duration: 1 });
                        
                        // Find marker in dbFasilitasLayer and open popup
                        dbFasilitasLayer.eachLayer(function(marker) {
                            if (marker.getLatLng().equals(latlng)) {
                                marker.openPopup();
                            }
                        });

                        searchInput.value = f.properties.nama;
                        suggestionsContainer.classList.remove('active');
                    });
                    suggestionsContainer.appendChild(item);
                });
            }
            suggestionsContainer.classList.add('active');
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.classList.remove('active');
            }
        });

        function loadDbFasilitas() {
            fetch('/api/fasilitas')
                .then(res => res.json())
                .then(geojson => {
                    globalDbGeojson = geojson;
                    initCategoryFilter();
                    renderDbFasilitas(geojson);
                })
                .catch(err => console.warn('Gagal memuat fasilitas dari database:', err));
        }

        loadDbFasilitas();
        } // akhir initMap()

        </script>        
    </body>
</html>