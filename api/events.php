<?php
// SIIPAK - API JSON Events for FullCalendar
header('Content-Type: application/json');
require_once '../config/database.php';

$id_gedung = $_GET['id_gedung'] ?? null;

$sql = "SELECT t.*, g.nama_gedung, p.nama AS nama_penyewa 
        FROM transaksi t
        JOIN gedung g ON t.id_gedung = g.id_gedung
        JOIN penyewa p ON t.id_penyewa = p.id_penyewa
        WHERE t.status_transaksi NOT IN ('Ditolak', 'Dibatalkan')";

$params = [];
if (!empty($id_gedung)) {
    $sql .= " AND t.id_gedung = :id_gedung";
    $params[':id_gedung'] = $id_gedung;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Group transactions by id_penyewa to merge contiguous/overlapping bookings
$groups = [];
foreach ($rows as $row) {
    $key = $row['id_penyewa'];
    $groups[$key][] = $row;
}

$merged_rows = [];
foreach ($groups as $key => $group_rows) {
    // Sort transactions within group by tanggal_mulai
    usort($group_rows, function($a, $b) {
        return strcmp($a['tanggal_mulai'], $b['tanggal_mulai']);
    });
    
    $current = $group_rows[0];
    for ($i = 1; $i < count($group_rows); $i++) {
        $next = $group_rows[$i];
        
        $current_end = $current['tanggal_selesai'];
        $next_start = $next['tanggal_mulai'];
        
        // Contiguous check: next starts on or before current_end + 1 day
        $max_allowed_gap_date = date('Y-m-d', strtotime($current_end . ' +1 day'));
        
        if ($next_start <= $max_allowed_gap_date) {
            // Overlapping or contiguous: extend end date
            if ($next['tanggal_selesai'] > $current['tanggal_selesai']) {
                $current['tanggal_selesai'] = $next['tanggal_selesai'];
            }
            // Combine fields
            $current['nama_kegiatan'] .= " & " . $next['nama_kegiatan'];
            $current['kode_transaksi'] .= ", " . $next['kode_transaksi'];
            if (strpos($current['nama_gedung'], $next['nama_gedung']) === false) {
                $current['nama_gedung'] .= ", " . $next['nama_gedung'];
            }
            
            // Priority for status: Lunas > DP/Cicilan > Menunggu Pembayaran
            $status_priority = [
                'Lunas' => 3,
                'Selesai' => 3,
                'DP' => 2,
                'Cicilan' => 2,
                'Menunggu Pembayaran' => 1
            ];
            
            $curr_status = $current['status_transaksi'];
            $next_status = $next['status_transaksi'];
            
            $curr_prio = $status_priority[$curr_status] ?? 0;
            $next_prio = $status_priority[$next_status] ?? 0;
            
            if ($next_prio > $curr_prio) {
                $current['status_transaksi'] = $next['status_transaksi'];
            }
        } else {
            $merged_rows[] = $current;
            $current = $next;
        }
    }
    $merged_rows[] = $current;
}

$events = [];
foreach ($merged_rows as $row) {
    // FullCalendar end date is exclusive, so we add +1 day
    $end_date = date('Y-m-d', strtotime($row['tanggal_selesai'] . ' +1 day'));
    
    $color = '#f59e0b'; // default warning/orange for Menunggu Pembayaran
    if ($row['status_transaksi'] === 'Lunas') {
        $color = '#10b981'; // green for Lunas (locked)
    } elseif (in_array($row['status_transaksi'], ['DP', 'Cicilan'])) {
        $color = '#2563eb'; // blue for DP/Cicilan
    }

    $events[] = [
        'id' => $row['id_transaksi'],
        'title' => 'Full',
        'start' => $row['tanggal_mulai'],
        'end' => $end_date,
        'backgroundColor' => $color,
        'borderColor' => $color,
        'textColor' => '#ffffff',
        'extendedProps' => [
            'kode_transaksi' => $row['kode_transaksi'],
            'nama_kegiatan' => $row['nama_kegiatan'],
            'nama_gedung' => $row['nama_gedung'],
            'nama_penyewa' => $row['nama_penyewa'],
            'status' => $row['status_transaksi'],
            'tanggal_mulai' => $row['tanggal_mulai'],
            'tanggal_selesai' => $row['tanggal_selesai']
        ]
    ];
}

echo json_encode($events);
