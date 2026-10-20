<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>NairaBill · Dashboard</title>
    <style>
        :root {
            --ink: #152a24;
            --muted: #6b7b74;
            --line: #e1e9e4;
            --green: #147d58;
            --bg: #f5f8f5
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font: 14px/1.5 Inter, system-ui, sans-serif
        }

        .shell {
            display: grid;
            grid-template-columns: 230px minmax(0, 1fr);
            min-height: 100vh
        }

        .side {
            position: sticky;
            top: 0;
            height: 100vh;
            align-self: start;
            background: #10251e;
            color: #dcebe3;
            padding: 25px 16px
        }

        .brand {
            font-size: 24px;
            font-weight: 900;
            margin: 0 12px 32px
        }

        .brand span {
            color: #9fe3ad
        }

        .side a {
            display: block;
            text-decoration: none;
            color: #dcebe3;
            padding: 11px 12px;
            border-radius: 8px;
            margin: 4px 0
        }

        .side a:hover,
        .side a.active {
            background: #234638
        }

        .side .hint {
            color: #9bb5a7;
            font-size: 12px;
            padding: 20px 12px
        }

        .main {
            padding: 30px;
            min-width: 0
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px
        }

        .top h1 {
            margin: 0;
            font-size: 28px;
            letter-spacing: -1px
        }

        .muted {
            color: var(--muted)
        }

        .btn,
        button {
            display: inline-block;
            border: 0;
            border-radius: 8px;
            background: var(--green);
            color: #fff;
            padding: 10px 13px;
            text-decoration: none;
            font: inherit;
            font-weight: 750;
            cursor: pointer
        }

        .btn.light {
            background: #fff;
            color: var(--ink);
            border: 1px solid var(--line)
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px
        }

        .card,
        .panel {
            background: white;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 20px
        }

        .card .num {
            font-size: 27px;
            font-weight: 850;
            letter-spacing: -1px;
            margin-top: 6px
        }

        .sectionhead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px
        }

        .sectionhead h2 {
            font-size: 18px;
            margin: 0
        }

        table {
            border-collapse: collapse;
            width: 100%;
            min-width: 680px
        }

        th,
        td {
            text-align: left;
            padding: 12px 9px;
            border-bottom: 1px solid #edf1ee
        }

        th {
            font-size: 11px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .5px
        }

        td {
            font-size: 13px
        }

        .tablewrap {
            overflow: auto
        }

        .pill {
            display: inline-block;
            background: #e9f6ed;
            color: #236747;
            border-radius: 20px;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 800
        }

        .pill.bad {
            background: #fff0ed;
            color: #a23d2d
        }

        .alert {
            padding: 12px;
            border-radius: 9px;
            margin-bottom: 16px
        }

        .success {
            background: #e9f6ed;
            color: #236747
        }

        .error {
            background: #fff0ed;
            color: #a23d2d
        }

        .formgrid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px
        }

        .field label {
            display: block;
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 5px
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            border: 1px solid #d8e2db;
            border-radius: 8px;
            padding: 11px;
            font: inherit;
            background: white
        }

        .field.full {
            grid-column: 1/-1
        }

        .note {
            background: #f0f7f1;
            border-radius: 9px;
            padding: 12px;
            margin: 10px 0;
            color: #355a48;
            overflow-wrap: anywhere
        }

        .keyform {
            display: flex;
            gap: 8px;
            flex-wrap: wrap
        }

        .keyform input {
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: 8px;
            font: inherit
        }

        .keyform input[name=label] {
            flex: 1;
            min-width: 140px
        }

        .key {
            font: 12px ui-monospace, monospace;
            overflow-wrap: anywhere
        }

        @media(max-width:900px) {
            .shell {
                grid-template-columns: 1fr
            }

            .side {
                padding: 14px
            }

            .side nav {
                display: flex;
                gap: 4px;
                overflow: auto
            }

            .side a {
                white-space: nowrap
            }

            .brand {
                margin: 0 10px 10px
            }

            .side .hint {
                display: none
            }

            .main {
                padding: 16px
            }

            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }
        }

        @media(max-width:520px) {
            .formgrid {
                grid-template-columns: 1fr
            }

            .field.full {
                grid-column: auto
            }

            .top h1 {
                font-size: 22px
            }

            .cards .num {
                font-size: 22px
            }
        }
    </style>
</head>

<body>
<div class="shell">
    <aside class="side">
        <div class="brand">Naira<span>Bill</span></div>
        <nav>
            <a class="active" href="/customer/dashboard">Overview</a>
            <a href="/customer/invoices">Invoices</a>
            <a href="#create-invoice">Create invoice</a>
            <a href="#api-keys">API keys</a>
            <a href="/invoice-platform">API docs</a>
            <a href="/customer/logout">Sign out</a>
        </nav>
        <div class="hint">Business workspace<br><strong><?= esc($customer['workspace_name']) ?></strong></div>
    </aside>
    <main class="main">
    <?= $yield ?? '' ?>
    </main>
</div>
</body>
</html>