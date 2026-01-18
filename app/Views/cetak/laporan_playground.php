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
    <?php if ($jenis !== "Tahunan"): ?>
        <?php $tot_rangkuman = (int)$rangkuman['masuk'] - (int)$rangkuman['keluar']; ?>
        <h4>A. RANGKUMAN</h4>
        <table style="width: 100%;">
            <tr>
                <th>NO.</th>
                <th>BULAN</th>
                <th>DIVISI</th>
                <th>MASUK</th>
                <th>KELUAR</th>
                <th>SALDO</th>
            </tr>
            <?php foreach ($rangkuman['data'] as $k => $i): ?>
                <?php $rowspan = count($i['data']); ?>
                <?php dd($rowspan); ?>
                <?php foreach ($i['data'] as $key => $d): ?>
                    <tr>
                        <td rowspan="<?= $rowspan; ?>" style="text-align:center;"><?= ($k + 1); ?></td>
                        <td rowspan="<?= $rowspan; ?>"><?= $i['bulan']; ?></td>
                        <td><?= $d['divisi']; ?></td>
                        <td style="text-align: right;"><?= angka($d['masuk']); ?></td>
                        <td style="text-align: right;"><?= angka($d['keluar']); ?></td>
                        <td style="text-align: right;"><?= (((int)$d['total']) < 0 ? "- " : "") . angka($d['total']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <tr>
                <th colspan="5" style="text-align: center;">TOTAL</th>
                <th style="text-align: right;"><?= ($tot_rangkuman < 0 ? "- " : "") . angka($tot_rangkuman); ?></th>
            </tr>
        </table>
    <?php endif; ?>
    <?php if ($jenis == "All"): ?>
        <?php $rowspan = count($data['data']); ?>
        <h4>B. PEMASUKAN [<?= angka($data['masuk']); ?>]</h4>
        <table style="width: 100%;">
            <tr>
                <th>No.</th>
                <th>Divisi</th>
                <th>Tgl</th>
                <th>Barang</th>
                <th>Harga</th>
                <th>Qty</th>
                <th>Diskon</th>
                <th>Biaya</th>
            </tr>
            <?php foreach ($data['data'] as $i): ?>
                <?php foreach ($i['data'] as $d): ?>
                    <?php if ($d['judul'] == "Masuk"): ?>
                        <?php foreach ($d['data'] as $k => $dt): ?>
                            <tr>
                                <td style="text-align: center;"><?= ($k + 1); ?></td>
                                <td><?= $i['divisi']; ?></td>
                                <td style="text-align: center;"><?= date('d-m-Y', $dt['tgl']); ?></td>
                                <td><?= $dt['barang']; ?></td>
                                <td style="text-align: right;"><?= angka($dt['harga']); ?></td>
                                <td style="text-align: right;"><?= angka($dt['qty']); ?></td>
                                <td style="text-align: right;"><?= angka($dt['diskon']); ?></td>
                                <td style="text-align: right;"><?= angka($dt['biaya']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </table>
        <h4>C. PENGELUARAN [<?= angka($data['keluar']); ?>]</h4>
        <table style="width: 100%;">
            <tr>
                <th>No.</th>
                <th>Divisi</th>
                <th>Tgl</th>
                <th>Barang</th>
                <th>Harga</th>
                <th>Qty</th>
                <th>Diskon</th>
                <th>Biaya</th>
            </tr>
            <?php foreach ($data['data'] as $i): ?>
                <?php foreach ($i['data'] as $d): ?>
                    <?php if ($d['judul'] == "Keluar"): ?>
                        <?php foreach ($d['data'] as $k => $dt): ?>
                            <tr>
                                <td style="text-align: center;"><?= ($k + 1); ?></td>
                                <td><?= $i['divisi']; ?></td>
                                <td style="text-align: center;"><?= date('d-m-Y', $dt['tgl']); ?></td>
                                <td><?= $dt['barang']; ?></td>
                                <td style="text-align: right;"><?= angka($dt['harga']); ?></td>
                                <td style="text-align: right;"><?= angka($dt['qty']); ?></td>
                                <td style="text-align: right;"><?= angka($dt['diskon']); ?></td>
                                <td style="text-align: right;"><?= angka($dt['biaya']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <?php if ($jenis == "Harian"): ?>
        <h4>B. DETAIL [<?= angka($data['total']['transaksi'] - $data['total']['pengeluaran']); ?>]</h4>
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
        <h4>B. DETAIL [<?= angka($data['total']['transaksi'] - $data['total']['pengeluaran']); ?>]</h4>
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
        <h4>A. DETAIL [<?= angka($data['total']['transaksi'] - $data['total']['pengeluaran']); ?>]</h4>
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