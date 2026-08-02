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

$events = [];
foreach ($rows as $row) {
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
        'title' => $row['nama_kegiatan'] . ' [' . $row['nama_gedung'] . ']',
        'start' => $row['tanggal_mulai'],
        'end' => $end_date,
        'backgroundColor' => $color,
        'borderColor' => $color,
        'textColor' => '#ffffff',
        'extendedProps' => [
            'kode_transaksi' => $row['kode_transaksi'],
            'nama_gedung' => $row['nama_gedung'],
            'nama_penyewa' => $row['nama_penyewa'],
            'status' => $row['status_transaksi'],
            'tanggal_mulai' => $row['tanggal_mulai'],
            'tanggal_selesai' => $row['tanggal_selesai']
        ]
    ];
}

echo json_encode($events);
