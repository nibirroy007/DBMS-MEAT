<?php
// Common header with navigation bar (enhanced)
$current = basename($_SERVER['PHP_SELF']); // mark active nav
function active($file, $current)
{
    return $current === $file ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meat Market Dashboard</title>

    <!-- System font stack; no external libs required -->
    <style>
        :root {
            --bg-grad: linear-gradient(120deg, #eef2ff 0%, #e0f7ff 35%, #fef6ff 70%, #ffffff 100%);
            --glass: rgba(255, 255, 255, .68);
            --text: #0f172a;
            /* slate-900 */
            --muted: #475569;
            /* slate-600 */
            --brand: #4f46e5;
            /* indigo-600 */
            --brand-2: #06b6d4;
            /* cyan-500 */
            --ring: rgba(79, 70, 229, .25);
            --card: #ffffff;
            --border: #e5e7eb;
            --nav: rgba(255, 255, 255, .55);
            --nav-border: rgba(255, 255, 255, .4);
            --shadow: 0 10px 30px rgba(0, 0, 0, .08);
        }

        [data-theme="dark"] {
            --bg-grad: linear-gradient(120deg, #0b1220 0%, #0e1c28 35%, #1a1530 70%, #0b1220 100%);
            --glass: rgba(20, 24, 38, .6);
            --text: #e5e7eb;
            /* slate-200 */
            --muted: #94a3b8;
            /* slate-400 */
            --brand: #818cf8;
            /* indigo-400 */
            --brand-2: #22d3ee;
            /* cyan-400 */
            --card: #111827;
            --border: #293241;
            --nav: rgba(17, 24, 39, .7);
            --nav-border: rgba(255, 255, 255, .08);
            --ring: rgba(129, 140, 248, .28);
            --shadow: 0 10px 30px rgba(0, 0, 0, .35);
        }

        * {
            box-sizing: border-box
        }

        html,
        body {
            height: 100%
        }

        body {
            margin: 0;
            padding: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
            color: var(--text);
            background: var(--bg-grad) fixed;
            letter-spacing: .2px;
        }

        /* Glass, sticky navbar */
        .nav-wrap {
            position: sticky;
            top: 0;
            z-index: 999;
            backdrop-filter: blur(10px);
            background: var(--nav);
            border-bottom: 1px solid var(--nav-border);
            box-shadow: var(--shadow);
        }

        .nav {
            display: flex;
            align-items: center;
            gap: 16px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 10px 18px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            letter-spacing: .3px;
            color: var(--text);
            text-decoration: none;
            white-space: nowrap;
        }

        .brand-badge {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: radial-gradient(120px 60px at -10% 120%, var(--brand) 0%, var(--brand-2) 70%, transparent 70%);
            color: white;
            font-weight: 900;
            font-size: 18px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, .15);
        }

        .nav-main {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 6px;
            flex-wrap: wrap;
        }

        .nav-main a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 10px;
            color: var(--muted);
            text-decoration: none;
            font-weight: 600;
            border: 1px solid transparent;
        }

        .nav-main a:hover {
            background: rgba(0, 0, 0, .04);
            color: var(--text);
        }

        [data-theme="dark"] .nav-main a:hover {
            background: rgba(255, 255, 255, .04);
        }

        .nav-main a.active {
            color: var(--text);
            background: linear-gradient(180deg, rgba(255, 255, 255, .55), rgba(255, 255, 255, .35));
            border: 1px solid var(--nav-border);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .15);
        }

        [data-theme="dark"] .nav-main a.active {
            background: rgba(255, 255, 255, .06);
            border-color: var(--nav-border);
        }

        .spacer {
            flex: 1
        }

        .nav-tools {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 260px;
        }

        .search {
            position: relative;
            flex: 1;
            min-width: 140px;
        }

        .search input {
            width: 100%;
            padding: 9px 12px 9px 36px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--glass);
            color: var(--text);
            outline: none;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .03);
        }

        .search input:focus {
            box-shadow: 0 0 0 4px var(--ring);
        }

        .search .ico {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            opacity: .6;
            font-size: 14px;
        }

        .btn-icon {
            display: inline-grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--glass);
            cursor: pointer;
            user-select: none;
        }

        .btn-icon:hover {
            box-shadow: 0 0 0 4px var(--ring);
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid var(--nav-border);
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            box-shadow: 0 2px 8px rgba(0, 0, 0, .18);
        }

        /* Mobile */
        .hamburger {
            display: none;
        }

        @media (max-width: 980px) {
            .nav {
                padding: 10px 14px;
            }

            .nav-main {
                display: none;
                width: 100%;
            }

            .nav.open .nav-main {
                display: flex;
            }

            .hamburger {
                display: inline-grid;
            }

            .nav-tools {
                min-width: 0;
            }
        }

        /* Page container (kept same class name to avoid breaking pages) */
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: var(--card);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        /* Table + forms default (still compatible with your pages) */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        table,
        th,
        td {
            border: 1px solid var(--border);
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        th {
            background: rgba(0, 0, 0, .03);
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--glass);
            color: var(--text);
        }

        input:focus,
        select:focus {
            outline: none;
            box-shadow: 0 0 0 4px var(--ring);
        }

        button {
            padding: 10px 16px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            color: #fff;
            background: linear-gradient(90deg, var(--brand), var(--brand-2));
            font-weight: 700;
            letter-spacing: .2px;
            box-shadow: 0 6px 16px rgba(79, 70, 229, .25);
        }

        button:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }
    </style>
</head>

<body>

    <!-- NAV -->
    <div class="nav-wrap">
        <div class="nav" id="topNav">
            <button class="btn-icon hamburger" id="hamburger" title="Menu" aria-label="Open menu">☰</button>

            <a href="meat_products.php" class="brand" title="Dashboard Home">
                <div class="brand-badge">MM</div>
                <div>Meat Market <span style="opacity:.6; font-weight:700;">Dashboard</span></div>
            </a>

            <div class="nav-main" id="navMain">
                <a class="<?= active('meat_products.php', $current) ?>" href="meat_products.php">🧾 Meat Products</a>
                <a class="<?= active('production_volume.php', $current) ?>" href="production_volume.php">📦 Production</a>
                <a class="<?= active('price_trends.php', $current) ?>" href="price_trends.php">📈 Price Trends</a>
                <a class="<?= active('consumption.php', $current) ?>" href="consumption.php">🍽️ Consumption</a>
                <a class="<?= active('processing.php', $current) ?>" href="processing.php">🔪 Processing</a>
                <a class="<?= active('supply_demand.php', $current) ?>" href="supply_demand.php">🔄 Supply & Demand</a>
                <a class="<?= active('insights.php', $current) ?>" href="insights.php">🧠 Analysts</a>
            </div>

            <div class="spacer"></div>

        </div>
    </div>

    <!-- PAGE WRAPPER (kept as .container so existing pages work) -->
    <div class="container">
        <script>
            // Theme persistence
            (function() {
                const root = document.documentElement;
                const saved = localStorage.getItem('mm_theme');
                if (saved) {
                    root.setAttribute('data-theme', saved);
                }
                document.getElementById('themeToggle').addEventListener('click', () => {
                    const cur = root.getAttribute('data-theme') || 'light';
                    const next = cur === 'light' ? 'dark' : 'light';
                    root.setAttribute('data-theme', next);
                    localStorage.setItem('mm_theme', next);
                });
            })();

            // Mobile hamburger
            (function() {
                const nav = document.getElementById('topNav');
                const btn = document.getElementById('hamburger');
                btn.addEventListener('click', () => nav.classList.toggle('open'));
            })();
        </script>