<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $judul1 . $judul2; ?></title>
    <style>
        body {
            font-size: 12px;
        }

        table,
        th,
        td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 5px;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div style="text-align: center;margin-bottom:10px"><?= $logo; ?></div>
    <h4 style="text-align: center;margin-bottom:-20px"><?= $judul1; ?></h4>
    <h3 style="text-align: center;"><?= $judul2; ?></h3>
    <?php if ($jenis !== "Tahunan" && $jenis !== "Bulanan"): ?>
        <?php $tot_rangkuman = (int)$rangkuman['total']['transaksi'] - (int)$rangkuman['total']['pengeluaran']; ?>
        <h4>A. RANGKUMAN</h4>
        <table style="width: 100%;">
            <tr>
                <th>NO.</th>
                <th>BULAN</th>
                <th>MASUK</th>
                <th>KELUAR</th>
                <th>SALDO</th>
            </tr>
            <?php foreach ($rangkuman['data'] as $k => $i): ?>
                <tr>
                    <td style="text-align:center;"><?= ($k + 1); ?></td>
                    <td><?= $i['tgl']; ?></td>
                    <td style="text-align: right;"><?= angka($i['masuk']); ?></td>
                    <td style="text-align: right;"><?= angka($i['keluar']); ?></td>
                    <td style="text-align: right;"><?= angka((int)$i['masuk'] - (int)$i['keluar']); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <th colspan="4" style="text-align: center;">TOTAL</th>
                <th style="text-align: right;"><?= angka($tot_rangkuman); ?></th>
            </tr>
        </table>
    <?php endif; ?>
    <?php $tot_data = (int)$data['total']['transaksi'] - (int)$data['total']['pengeluaran']; ?>
    <?php if ($jenis == "All"): ?>
        <h4>B. PEMASUKAN [<?= angka($data['data']['transaksi']['total']); ?>]</h4>
        <table style="width: 100%;">
            <tr>
                <th>No.</th>
                <th>Tgl</th>
                <th>Barang</th>
                <th>Harga</th>
                <th>Qty</th>
                <th>Diskon</th>
                <th>Biaya</th>
            </tr>
            <?php foreach ($data['data']['transaksi']['data'] as $k => $i): ?>
                <tr>
                    <td style="text-align: center;"><?= ($k + 1); ?></td>
                    <td style="text-align: center;"><?= date('d-m-Y', $i['tgl']); ?></td>
                    <td><?= $i['barang']; ?></td>
                    <td style="text-align: right;"><?= angka($i['harga']); ?></td>
                    <td style="text-align: right;"><?= angka($i['qty']); ?></td>
                    <td style="text-align: right;"><?= angka($i['diskon']); ?></td>
                    <td style="text-align: right;"><?= angka($i['biaya']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <h4>C. PENGELUARAN [<?= angka($data['data']['pengeluaran']['total']); ?>]</h4>
        <table style="width: 100%;">
            <tr>
                <th>No.</th>
                <th>Tgl</th>
                <th>Barang</th>
                <th>Harga</th>
                <th>Qty</th>
                <th>Diskon</th>
                <th>Biaya</th>
            </tr>
            <?php foreach ($data['data']['pengeluaran']['data'] as $k => $i): ?>
                <tr>
                    <td style="text-align: center;"><?= ($k + 1); ?></td>
                    <td style="text-align: center;"><?= date('d-m-Y', $i['tgl']); ?></td>
                    <td><?= $i['barang']; ?></td>
                    <td style="text-align: right;"><?= angka($i['harga']); ?></td>
                    <td style="text-align: right;"><?= angka($i['qty']); ?></td>
                    <td style="text-align: right;"><?= angka($i['diskon']); ?></td>
                    <td style="text-align: right;"><?= angka($i['biaya']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <?php if ($jenis == "Harian"): ?>
        <h4>B. DETAIL [<?= angka($data['total']['transaksi']) . " - " . angka($data['total']['pengeluaran']); ?>= <?= angka($tot_data); ?>]</h4>
        <table style="width: 100%;">
            <tr>
                <th>Tgl</th>
                <th>Pemasukan</th>
                <th>Pengeluaran</th>
                <th>Saldo</th>
            </tr>
            <?php foreach ($data['data'] as $i): ?>
                <tr>
                    <td style="text-align: center;"><?= $i['tgl']; ?></td>
                    <td style="text-align: right;"><?= angka($i['masuk']); ?></td>
                    <td style="text-align: right;"><?= angka($i['keluar']); ?></td>
                    <td style="text-align: right;"><?= angka($i['keluar'] - $i['masuk']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <?php if ($jenis == "Bulanan"): ?>
        <h4>B. DETAIL [<?= angka($data['total']['transaksi']) . " - " . angka($data['total']['pengeluaran']); ?>= <?= angka($tot_data); ?>]</h4>
        <table style="width: 100%;">
            <tr>
                <th>No.</th>
                <th>Bulan</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Saldo</th>
            </tr>
            <?php foreach ($data['data'] as $k => $i): ?>
                <tr>
                    <td style="text-align:center;"><?= ($k + 1); ?></td>
                    <td><?= $i['tgl']; ?></td>
                    <td style="text-align: right;"><?= angka($i['masuk']); ?></td>
                    <td style="text-align: right;"><?= angka($i['keluar']); ?></td>
                    <td style="text-align: right;"><?= angka($i['keluar'] - $i['masuk']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <?php if ($jenis == "Tahunan"): ?>
        <h4>B. DETAIL [<?= angka($data['total']['transaksi']) . " - " . angka($data['total']['pengeluaran']); ?>= <?= angka($tot_data); ?>]</h4>
        <table style="width: 100%;">
            <tr>
                <th>No.</th>
                <th>Tahun</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Saldo</th>
            </tr>
            <?php foreach ($data['data'] as $k => $i): ?>
                <tr>
                    <td style="text-align:center;"><?= ($k + 1); ?></td>
                    <td style="text-align: center;"><?= $i['tgl']; ?></td>
                    <td style="text-align: right;"><?= angka($i['masuk']); ?></td>
                    <td style="text-align: right;"><?= angka($i['keluar']); ?></td>
                    <td style="text-align: right;"><?= angka($i['masuk'] - $i['keluar']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>

</html>