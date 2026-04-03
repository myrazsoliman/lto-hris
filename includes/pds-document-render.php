<?php

function pds_document_employee_id_from_user($userId)
{
    return resolve_employee_id_for_user((int) $userId);
}

function pds_document_value($value, $fallback = 'N/A')
{
    $value = is_string($value) ? trim($value) : $value;
    if ($value === null || $value === '') {
        return $fallback;
    }
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function pds_document_date($value)
{
    if (empty($value)) {
        return 'N/A';
    }
    $ts = strtotime((string) $value);
    return $ts ? date('F j, Y', $ts) : htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function pds_document_yes_no($value)
{
    $value = strtolower(trim((string) $value));
    if ($value === 'yes') {
        return 'Yes';
    }
    if ($value === 'no') {
        return 'No';
    }
    return 'N/A';
}

function pds_document_image_markup($path, $alt, $embed = false)
{
    $path = trim((string) $path);
    if ($path === '') {
        return '<div class="img-placeholder">No image uploaded</div>';
    }

    $src = $path;
    if ($embed) {
        $absolute = realpath(__DIR__ . '/../' . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR));
        if ($absolute && is_file($absolute)) {
            $mime = mime_content_type($absolute) ?: 'image/png';
            $src = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($absolute));
        }
    }

    return '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '">';
}

function pds_document_get_context($employeeId, $year)
{
    $pdsRecord = get_pds_record($employeeId, $year);
    if (!$pdsRecord) {
        return null;
    }

    $pdsId = (int) $pdsRecord['id'];
    return [
        'employeeId' => (int) $employeeId,
        'year' => (int) $year,
        'pdsRecord' => $pdsRecord,
        'personal' => get_pds_personal_info($pdsId) ?: [],
        'family' => get_pds_family_background($pdsId) ?: [],
        'children' => get_pds_children($pdsId),
        'education' => get_pds_education($pdsId),
        'eligibility' => get_pds_civil_service_eligibility($pdsId),
        'workExperience' => get_pds_work_experience($pdsId),
        'voluntaryWork' => get_pds_voluntary_work($pdsId),
        'training' => get_pds_training_programs($pdsId),
        'otherInfo' => get_pds_other_information($pdsId) ?: [],
        'questions' => get_pds_questions($pdsId) ?: [],
        'references' => get_pds_references($pdsId),
        'governmentId' => get_pds_government_id($pdsId) ?: [],
        'signature' => get_pds_signature($pdsId) ?: [],
    ];
}

function pds_document_render_html(array $ctx, $printMode = false, $embedImages = false)
{
    extract($ctx, EXTR_SKIP);
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDS Document View</title>
    <style>
        :root{--blue:#0f4c81;--blue2:#0a3558;--ink:#1b2430;--muted:#607286;--line:#c7d2df;--bg:#f4f7fa;--card:#fff}
        *{box-sizing:border-box}body{margin:0;background:#e9eef3;color:var(--ink);font-family:"Segoe UI",Arial,sans-serif}
        .toolbar{<?= $printMode ? 'display:none;' : 'position:sticky;top:0;z-index:5;display:flex;justify-content:space-between;gap:12px;align-items:center;padding:14px 20px;background:rgba(10,28,46,.95);color:#fff' ?>}
        .toolbar strong{font-size:13px;letter-spacing:.12em;text-transform:uppercase}.actions{display:flex;gap:10px;flex-wrap:wrap}
        .btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:10px 16px;border-radius:10px;border:1px solid rgba(255,255,255,.22);background:rgba(255,255,255,.08);color:#fff;text-decoration:none;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;cursor:pointer}
        .btn.primary{background:linear-gradient(180deg,#d0a642,#ba8c1d);border-color:transparent;color:#10243a}
        .shell{padding:22px}.page{width:210mm;min-height:297mm;margin:0 auto 20px;background:var(--card);box-shadow:0 20px 40px rgba(18,35,52,.1);border-top:6px solid var(--blue);padding:16mm 14mm 14mm;position:relative}
        .break{page-break-after:always}.header{display:grid;grid-template-columns:1fr 42mm;gap:14px;align-items:start;border-bottom:2px solid var(--blue);padding-bottom:10px;margin-bottom:12px}
        .kicker{color:var(--blue);font-size:11px;font-weight:800;letter-spacing:.18em;text-transform:uppercase}.title{margin:6px 0 0;font-size:24px;font-weight:900;letter-spacing:.04em;text-transform:uppercase}.subtitle{margin:4px 0 0;color:var(--muted);font-size:12px;font-weight:700}
        .photo,.sig-card{border:1px solid var(--line);background:var(--bg)}.photo{min-height:48mm;padding:6px;display:flex;align-items:center;justify-content:center}.photo img,.sig-grid img{width:100%;height:100%;object-fit:contain;display:block}
        .img-placeholder{color:var(--muted);font-size:11px;text-align:center;font-weight:700}.meta{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin:12px 0 14px}.meta-item{border:1px solid var(--line);background:var(--bg);padding:8px 10px}.meta-item small{display:block;color:var(--muted);font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.meta-item strong{display:block;margin-top:4px;font-size:13px}
        .section{margin-top:12px;border:1px solid var(--line)}.section h3{margin:0;background:linear-gradient(90deg,var(--blue),var(--blue2));color:#fff;padding:8px 10px;font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
        .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr))}.field{border-top:1px solid var(--line);border-right:1px solid var(--line);padding:8px 10px;min-height:58px}.grid .field:nth-child(4n){border-right:0}.field.full{grid-column:1/-1;border-right:0}.field.half{grid-column:span 2}.label{color:var(--muted);font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.value{margin-top:5px;font-size:13px;font-weight:700;line-height:1.45;word-break:break-word}
        table{width:100%;border-collapse:collapse}th,td{border-top:1px solid var(--line);border-right:1px solid var(--line);padding:8px 9px;vertical-align:top;text-align:left;font-size:12px}th:last-child,td:last-child{border-right:0}th{background:#eef3f8;color:var(--blue2);font-size:10px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}
        .note,.list,.q{border-top:1px solid var(--line)}.note{padding:8px 10px;color:var(--muted);font-size:11px}.list{display:grid;gap:8px;padding:10px}.list-item{border:1px solid #d8e0e8;background:#fbfcfd;padding:8px 10px}.q{padding:8px 10px}.q strong{font-size:12px;line-height:1.5}.q div{margin-top:4px;color:var(--muted);font-size:12px;line-height:1.5}
        .sig-grid{display:grid;grid-template-columns:1.4fr .8fr .8fr;gap:12px;margin-top:10px}.sig-card{min-height:42mm;padding:8px}.sig-card small{display:block;color:var(--muted);font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px}
        .footer{position:absolute;left:14mm;right:14mm;bottom:10mm;display:flex;justify-content:space-between;gap:12px;color:var(--muted);font-size:10px;border-top:1px solid var(--line);padding-top:6px}
        @media print{body{background:#fff}.toolbar{display:none}.shell{padding:0}.page{margin:0;box-shadow:none;width:auto;min-height:auto}}
        @media (max-width:980px){.shell{padding:12px}.page{width:100%;min-height:auto;padding:18px}.header,.meta,.grid,.sig-grid{grid-template-columns:1fr}.grid .field:nth-child(4n){border-right:1px solid var(--line)}.field,.field.full,.field.half{grid-column:auto;border-right:0}}
    </style>
</head>
<body>
    <div class="toolbar">
        <strong>Official PDS Document View</strong>
        <div class="actions">
            <a href="pds.php?employee_id=<?php echo (int) $employeeId; ?>" class="btn">Back To PDS</a>
            <button type="button" class="btn primary" onclick="window.print()">Print Document</button>
        </div>
    </div>
    <div class="shell">
        <?php include __DIR__ . '/pds-document-render-sections.php'; ?>
    </div>
</body>
</html>
    <?php
    return ob_get_clean();
}
