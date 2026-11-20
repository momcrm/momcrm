{{-- The look every PDF shares: invoices, receipts and statements. --}}
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
            color: #1f1d1a;
        }
        .accent-bar { height: 6px; background: #4b5bd6; }
        .page { padding: 40px 48px 120px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; padding: 0; }
        .muted { color: #77736c; }
        .label {
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #8b877f;
        }
        .from-name { font-size: 17pt; font-weight: bold; letter-spacing: -0.3px; }
        .from-details { margin-top: 4px; font-size: 8.5pt; color: #77736c; }
        .tax-id { margin-top: 4px; font-size: 8.5pt; font-weight: bold; color: #1f1d1a; }
        .title { font-size: 22pt; font-weight: bold; letter-spacing: 3px; color: #4b5bd6; text-align: right; line-height: 1; }
        .reference { text-align: right; font-size: 11pt; margin-top: 6px; }
        .stamp {
            display: inline-block;
            margin-top: 10px;
            padding: 3px 10px;
            border: 1.5px solid;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .stamp-paid { color: #25754b; border-color: #25754b; }
        .stamp-overdue { color: #b23c30; border-color: #b23c30; }
        .stamp-void { color: #77736c; border-color: #77736c; }
        .stamp-draft { color: #8b877f; border-color: #c9c5bd; }
        .divider { border-top: 1px solid #e7e4de; margin: 28px 0; }
        .bill-to-name { font-size: 12pt; font-weight: bold; margin-top: 6px; }
        .meta td { padding: 3px 0; }
        .meta td.value { text-align: right; font-weight: bold; }
        .summary {
            background: #f6f5f2;
            border-radius: 8px;
            padding: 14px 18px;
        }
        .summary .amount { font-size: 18pt; font-weight: bold; letter-spacing: -0.3px; }
        .items { margin-top: 28px; }
        .items th {
            padding: 9px 10px;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #8b877f;
            border-bottom: 1.5px solid #1f1d1a;
            text-align: left;
        }
        .items td { padding: 11px 10px; border-bottom: 1px solid #ecebe7; }
        .items .num { text-align: right; white-space: nowrap; }
        .items .first { padding-left: 0; }
        .items .last { padding-right: 0; }
        .items .period { font-size: 8pt; color: #8b877f; margin-top: 2px; }
        .totals { width: 46%; margin-left: 54%; margin-top: 18px; }
        .totals td { padding: 6px 10px; }
        .totals td.value { padding-right: 0; }
        .totals td.value { text-align: right; white-space: nowrap; }
        .totals tr.total td {
            padding-top: 12px;
            padding-bottom: 12px;
            border-top: 1.5px solid #1f1d1a;
            font-size: 12pt;
            font-weight: bold;
        }
        .totals tr.total td.value { color: #4b5bd6; }
        .payment {
            margin-top: 36px;
            padding: 14px 18px;
            border: 1px solid #e7e4de;
            border-left: 3px solid #4b5bd6;
            border-radius: 6px;
        }
        .payment p { margin: 6px 0 0; white-space: pre-line; }
        .notes { margin-top: 36px; }
        .notes p { margin: 6px 0 0; white-space: pre-line; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 18px 48px 26px;
            border-top: 1px solid #e7e4de;
            font-size: 8pt;
            color: #8b877f;
        }
    </style>
